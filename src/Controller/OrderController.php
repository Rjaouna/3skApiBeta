<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderLine;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\CustomerOrderRepository;
use App\Service\NotificationService;
use App\Service\EbpOrderExportService;
use App\Service\OrderMailer;
use App\Service\PricingService;
use App\Service\CompanySettingProvider;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly PricingService $pricingService
    ) {
    }

    #[Route('/commande/checkout', name: 'order_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CustomerOrderRepository $orderRepository,
        OrderMailer $orderMailer,
        NotificationService $notificationService,
        EbpOrderExportService $ebpOrderExports,
        CompanySettingProvider $companySettings,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('checkout_order', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée (token invalide).');
            return $this->redirectToRoute('cart_index');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('warning', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        if (method_exists($user, 'isActive') && $user->isActive() === false) {
            $this->addFlash('warning', 'Votre compte est désactivé. Impossible de passer commande.');
            return $this->redirectToRoute('cart_index');
        }

        $missing = $this->getMissingProfileFields($user);
        if (!empty($missing)) {
            $this->addFlash('warning', 'Merci de compléter votre profil avant de passer commande : Société, Ville, Adresse 1.');
            return $this->redirectToRoute('account_profile', ['fillRequired' => 1]);
        }

        $cart = $cartRepository->findOpenByUser($user);
        if (!$cart) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_index');
        }

        $cartItems = $cart->getItems()->toArray();
        if (count($cartItems) === 0) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_index');
        }

        $conn = $em->getConnection();
        $createdOrder = null;

        try {
            $conn->beginTransaction();

            if ($cart->getId() !== null) {
                $em->lock($cart, LockMode::PESSIMISTIC_WRITE);
            }

            $order = new CustomerOrder();
            $order->setOrderNumber($this->generateOrderNumber($orderRepository));
            $order->setCustomer($user);
            $order->setStatus(CustomerOrder::STATUS_NEW);
            $order->setCustomerEmail((string) $user->getEmail());
            $order->setCompanyName($user->getCompanyName());
            $order->setCustomerCode($user->getCustomerCode());
            $order->setAddress1($user->getAddress1());
            $order->setAddress2($user->getAddress2());
            $order->setCity($user->getCity());
            $order->setBillingCountryCode($this->countryCode($companySettings->get()->getCountry()));
            $ebpOrderExports->queue($order);

            $em->persist($order);

            $subtotalBeforeDiscount = 0.0;
            $totalDiscountAmount = 0.0;
            $totalFinal = 0.0;
            $validLinesCount = 0;

            $ratesUsed = [];
            $discountedLinesCount = 0;

            foreach ($cartItems as $cartItem) {
                $article = $cartItem->getArticle();
                $qty = max(0, (int) $cartItem->getQuantity());

                if (!$article) {
                    throw new \RuntimeException('Un article du panier est introuvable.');
                }

                if ($qty <= 0) {
                    continue;
                }

                $em->lock($article, LockMode::PESSIMISTIC_WRITE);

                $stock = max(0, (int) $article->getRealStock());
                if ($stock < $qty) {
                    throw new \RuntimeException(sprintf(
                        'Le stock de l’article %s a changé. Disponible : %d, demandé : %d.',
                        (string) $article->getId(),
                        $stock,
                        $qty
                    ));
                }

                $pricing = $this->pricingService->computeCartLinePricing(
                    $article,
                    $qty,
                    $user,
                    $cartItem->getUnitPriceTtc()
                );

                $line = new CustomerOrderLine();
                $line->setOrderRef($order);
                $line->setArticle($article);
                $line->setArticleCode((string) $article->getId());
                $line->setDesignation($article->getDesComClear());
                $line->setQuantity($qty);

                $line->setUnitPriceBeforeDiscountTtc($pricing['unitPriceBeforeDiscountFormatted']);
                $line->setUnitPriceBeforeDiscountHt($pricing['unitPriceBeforeDiscountHtFormatted']);
                $line->setVatRate($pricing['vatRateFormatted']);
                $line->setLineTotalBeforeDiscountTtc($pricing['lineTotalBeforeDiscountFormatted']);

                $line->setDiscountRate(
                    $pricing['hasReduction']
                        ? $this->pricingService->formatMoney((float) $pricing['globalReduction'])
                        : null
                );
                $line->setDiscountAmountTtc($pricing['lineDiscountAmountFormatted']);

                $line->setUnitPriceTtc($pricing['unitPriceFinalFormatted']);
                $line->setLineTotalTtc($pricing['lineTotalFinalFormatted']);

                $order->addLine($line);
                $em->persist($line);

                $article->setRealStock($stock - $qty);

                $subtotalBeforeDiscount += $pricing['lineTotalBeforeDiscount'];
                $totalDiscountAmount += $pricing['lineDiscountAmount'];
                $totalFinal += $pricing['lineTotalFinal'];
                $validLinesCount++;

                if ($pricing['hasReduction']) {
                    $rateStorage = $this->pricingService->formatMoney((float) $pricing['globalReduction']);
                    $ratesUsed[$rateStorage] = true;
                    $discountedLinesCount++;
                }
            }

            if ($validLinesCount === 0) {
                throw new \RuntimeException('Aucune ligne valide à commander.');
            }

            $order->setSubtotalTtc($this->pricingService->formatMoney($subtotalBeforeDiscount));
            $order->setTotalTtc($this->pricingService->formatMoney($totalFinal));

            $hasUniformDiscount =
                $discountedLinesCount > 0
                && $discountedLinesCount === $validLinesCount
                && count($ratesUsed) === 1;

            if ($hasUniformDiscount) {
                $uniformRate = array_key_first($ratesUsed);
                $order->setDiscountRate($uniformRate ?? '0.00');
                $order->setDiscountAmountTtc($this->pricingService->formatMoney($totalDiscountAmount));
            } else {
                $order->setDiscountRate('0.00');
                $order->setDiscountAmountTtc('0.00');
            }

            foreach ($cartItems as $cartItem) {
                $cart->removeItem($cartItem);
                $em->remove($cartItem);
            }

            $cart->setStatus('checked_out');
            $cart->touch();

            $newCart = new Cart();
            $newCart->setUser($user);
            $newCart->setStatus('open');
            $em->persist($newCart);

            $em->flush();
            $conn->commit();

            $createdOrder = $order;
            $notificationService->notifyNewOrder($createdOrder, $user);
        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }

            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('cart_index');
        }

        $mailReport = $orderMailer->sendOrderCreatedEmails($createdOrder);

        try {
            $ebpOrderExports->dispatch($createdOrder);
        } catch (\Throwable) {
            // The order is already confirmed. The scheduled synchronizer keeps
            // retrying its technical transmission without blocking the buyer.
            $this->addFlash('info', 'Votre commande est créée. Sa transmission vers EBP sera relancée automatiquement.');
        }

if (!$mailReport['admin_sent'] && !$mailReport['customer_sent']) {
    $details = !empty($mailReport['errors'])
        ? ' Détail : ' . implode(' | ', $mailReport['errors'])
        : '';

    $this->addFlash('warning', 'Commande créée, mais aucun email n’a été envoyé.' . $details);
} elseif (!empty($mailReport['errors'])) {
    $this->addFlash(
        'warning',
        'Commande créée, mais certains emails n’ont pas pu être envoyés. Détail : ' . implode(' | ', $mailReport['errors'])
    );
}

        $this->addFlash('success', 'Votre commande a bien été créée.');
        return $this->redirectToRoute('order_show', ['id' => $createdOrder->getId()]);
    }

    #[Route('/mes-commandes', name: 'order_index', methods: ['GET'])]
    public function index(CustomerOrderRepository $orderRepository)
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findByCustomer($user),
            'counts' => $orderRepository->countByStatusForCustomer($user),
            'labels' => CustomerOrder::getStatusLabels(),
        ]);
    }

    #[Route('/mes-commandes/{id}', name: 'order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CustomerOrder $order)
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($order->getCustomer()?->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/mes-commandes/stats', name: 'order_stats', methods: ['GET'])]
    public function stats(CustomerOrderRepository $orderRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false], 403);
        }

        $counts = $orderRepository->countByStatusForCustomer($user);

        return $this->json([
            'ok' => true,
            'counts' => $counts,
            'labels' => CustomerOrder::getStatusLabels(),
            'total' => array_sum($counts),
        ]);
    }

    private function getMissingProfileFields(User $user): array
    {
        $missing = [];

        if (!trim((string) $user->getCompanyName())) {
            $missing[] = 'companyName';
        }
        if (!trim((string) $user->getCity())) {
            $missing[] = 'city';
        }
        if (!trim((string) $user->getAddress1())) {
            $missing[] = 'address1';
        }

        return $missing;
    }

    /**
     * The EBP import format requires an ISO 3166-1 alpha-2 country code.
     * Existing installations are Moroccan by default, while a configured
     * two-letter code takes precedence for reusable deployments.
     */
    private function countryCode(?string $country): string
    {
        $country = strtoupper(trim((string) $country));
        if (preg_match('/^[A-Z]{2}$/D', $country) === 1) {
            return $country;
        }

        return match (mb_strtolower($country, 'UTF-8')) {
            'maroc', 'morocco' => 'MA',
            'france' => 'FR',
            'espagne', 'spain' => 'ES',
            'portugal' => 'PT',
            'italie', 'italy' => 'IT',
            default => 'MA',
        };
    }

    private function generateOrderNumber(CustomerOrderRepository $orderRepository): string
    {
        do {
            $number = '3SK-' . (new \DateTimeImmutable())->format('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($orderRepository->findOneBy(['orderNumber' => $number]));

        return $number;
    }
}
