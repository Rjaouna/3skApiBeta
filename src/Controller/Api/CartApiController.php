<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Service\PricingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cart', name: 'api_cart_')]
class CartApiController extends AbstractController
{
    public function __construct(
        private readonly PricingService $pricingService
    ) {
    }

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(
        CartRepository $cartRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'ok' => false,
                'message' => 'Non connecté.',
            ], 401);
        }

        $cart = $cartRepository->findOpenByUser($user);
        if (!$cart) {
            return $this->json([
                'ok' => true,
                'items' => [],
                'totalQty' => 0,
                'subtotalTtc' => '0.00',
                'totalBeforeDiscount' => '0.00',
                'totalDiscount' => '0.00',
                'totalFinal' => '0.00',
                'total' => '0.00',
                'totalAmount' => '0.00',
                'totalTtc' => '0.00',
                'summaryReductionText' => null,
                'hasReduction' => false,
                'imagesBaseUrl' => $this->resolveImagesBaseUrl(),
                'hasStockIssue' => false,
                'issuesCount' => 0,
            ]);
        }

        return $this->json($this->buildCartPayload($cart, $user));
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartItemRepository $cartItemRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'ok' => false,
                'message' => 'Non connecté.',
            ], 401);
        }

        $payload = $this->getPayload($request);
        $code = trim((string) ($payload['code'] ?? ''));
        $qty = max(1, (int) ($payload['qty'] ?? 1));

        if ($code === '') {
            return $this->json([
                'ok' => false,
                'message' => 'Code article obligatoire.',
            ], 422);
        }

        $article = $this->findArticleByCode($em, $code);
        if (!$article) {
            return $this->json([
                'ok' => false,
                'message' => 'Article introuvable.',
            ], 404);
        }

        $cart = $cartRepository->getOrCreateOpenCart($user);

        $existing = $cartItemRepository->findOneByCartAndArticle($cart, $article);
        $currentQty = $existing ? $existing->getQuantity() : 0;
        $nextQty = $currentQty + $qty;

        $stock = max(0, (int) $article->getRealStock());
        if ($stock <= 0) {
            return $this->json([
                'ok' => false,
                'message' => 'Cet article n’est plus disponible en stock.',
            ], 422);
        }

        if ($nextQty > $stock) {
            return $this->json([
                'ok' => false,
                'message' => sprintf('Stock insuffisant. Disponible : %d.', $stock),
            ], 422);
        }

        if (!$existing) {
            $existing = new CartItem();
            $existing->setCart($cart);
            $existing->setArticle($article);
            $existing->setUnitPriceTtc($this->pricingService->formatMoney((float) $article->getSalePriceVatIncluded()));
            $cart->addItem($existing);
            $em->persist($existing);
        }

        $existing->setQuantity($nextQty);
        $existing->setUnitPriceTtc($this->pricingService->formatMoney((float) $article->getSalePriceVatIncluded()));
        $cart->touch();

        $em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Article ajouté au panier.',
        ]);
    }

    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartItemRepository $cartItemRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'ok' => false,
                'message' => 'Non connecté.',
            ], 401);
        }

        $payload = $this->getPayload($request);
        $code = trim((string) ($payload['code'] ?? ''));
        $qty = max(0, (int) ($payload['qty'] ?? 0));

        if ($code === '') {
            return $this->json([
                'ok' => false,
                'message' => 'Code article obligatoire.',
            ], 422);
        }

        $article = $this->findArticleByCode($em, $code);
        if (!$article) {
            return $this->json([
                'ok' => false,
                'message' => 'Article introuvable.',
            ], 404);
        }

        $cart = $cartRepository->getOrCreateOpenCart($user);
        $item = $cartItemRepository->findOneByCartAndArticle($cart, $article);

        if ($qty === 0) {
            if ($item) {
                $cart->removeItem($item);
                $em->remove($item);
                $cart->touch();
                $em->flush();
            }

            return $this->json([
                'ok' => true,
                'message' => 'Article retiré du panier.',
            ]);
        }

        $stock = max(0, (int) $article->getRealStock());
        if ($qty > $stock) {
            return $this->json([
                'ok' => false,
                'message' => sprintf('Stock insuffisant. Disponible : %d.', $stock),
            ], 422);
        }

        if (!$item) {
            $item = new CartItem();
            $item->setCart($cart);
            $item->setArticle($article);
            $cart->addItem($item);
            $em->persist($item);
        }

        $item->setQuantity($qty);
        $item->setUnitPriceTtc($this->pricingService->formatMoney((float) $article->getSalePriceVatIncluded()));
        $cart->touch();

        $em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Panier mis à jour.',
        ]);
    }

    #[Route('/remove', name: 'remove', methods: ['POST'])]
    public function remove(
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartItemRepository $cartItemRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'ok' => false,
                'message' => 'Non connecté.',
            ], 401);
        }

        $payload = $this->getPayload($request);
        $code = trim((string) ($payload['code'] ?? ''));

        if ($code === '') {
            return $this->json([
                'ok' => false,
                'message' => 'Code article obligatoire.',
            ], 422);
        }

        $article = $this->findArticleByCode($em, $code);
        if (!$article) {
            return $this->json([
                'ok' => false,
                'message' => 'Article introuvable.',
            ], 404);
        }

        $cart = $cartRepository->findOpenByUser($user);
        if (!$cart) {
            return $this->json([
                'ok' => true,
                'message' => 'Panier déjà vide.',
            ]);
        }

        $item = $cartItemRepository->findOneByCartAndArticle($cart, $article);
        if ($item) {
            $cart->removeItem($item);
            $em->remove($item);
            $cart->touch();
            $em->flush();
        }

        return $this->json([
            'ok' => true,
            'message' => 'Article supprimé du panier.',
        ]);
    }

    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(
        EntityManagerInterface $em,
        CartRepository $cartRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'ok' => false,
                'message' => 'Non connecté.',
            ], 401);
        }

        $cart = $cartRepository->findOpenByUser($user);
        if (!$cart) {
            return $this->json([
                'ok' => true,
                'message' => 'Panier déjà vide.',
            ]);
        }

        foreach ($cart->getItems()->toArray() as $item) {
            $cart->removeItem($item);
            $em->remove($item);
        }

        $cart->touch();
        $em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Panier vidé.',
        ]);
    }

    private function buildCartPayload(Cart $cart, User $user): array
    {
        $items = [];
        $totalQty = 0;
        $subtotalBeforeDiscount = 0.0;
        $totalDiscount = 0.0;
        $totalFinal = 0.0;
        $issuesCount = 0;
        $imagesBaseUrl = $this->resolveImagesBaseUrl();

        $reductionValues = [];

        foreach ($cart->getItems() as $item) {
            $article = $item->getArticle();
            if (!$article) {
                continue;
            }

            $qty = max(0, (int) $item->getQuantity());
            $stock = max(0, (int) $article->getRealStock());
            $stockExceeded = $qty > $stock;

            if ($stockExceeded) {
                $issuesCount++;
            }

            $pricing = $this->pricingService->computeCartLinePricing(
                $article,
                $qty,
                $user,
                $item->getUnitPriceTtc()
            );

            if ($pricing['hasReduction'] && $pricing['globalReductionFormatted'] !== null) {
                $reductionValues[] = $pricing['globalReductionFormatted'];
            }

            $imageUrl = null;
            if ($imagesBaseUrl !== '' && $article->getUniqueId()) {
                $imageUrl = rtrim($imagesBaseUrl, '/') . '/' . $article->getUniqueId() . '.png';
            }

            $items[] = [
                'code' => $article->getId(),
                'uniqueId' => $article->getUniqueId(),
                'designation' => $article->getDesComClear(),
                'qty' => $qty,
                'realStock' => $stock,
                'availableQty' => $stock,
                'stockExceeded' => $stockExceeded,
                'stockMessage' => $stockExceeded
                    ? sprintf('Stock disponible : %d. Merci d’ajuster votre quantité.', $stock)
                    : null,

                'reduction' => $pricing['globalReductionFormatted'],
                'reductionLabel' => $pricing['reductionLabel'],

                'unitPriceTtc' => $pricing['unitPriceBeforeDiscountFormatted'],
                'unitPriceFinalTtc' => $pricing['unitPriceFinalFormatted'],
                'lineTotalBeforeDiscount' => $pricing['lineTotalBeforeDiscountFormatted'],
                'lineDiscount' => $pricing['lineDiscountAmountFormatted'],
                'lineTotal' => $pricing['lineTotalFinalFormatted'],

                'imageUrl' => $imageUrl,
            ];

            $totalQty += $qty;
            $subtotalBeforeDiscount += $pricing['lineTotalBeforeDiscount'];
            $totalDiscount += $pricing['lineDiscountAmount'];
            $totalFinal += $pricing['lineTotalFinal'];
        }

        $summaryReductionText = $this->pricingService->buildSummaryReductionText($reductionValues);

        return [
            'ok' => true,
            'items' => $items,
            'totalQty' => $totalQty,
            'subtotalTtc' => $this->pricingService->formatMoney($subtotalBeforeDiscount),
            'totalBeforeDiscount' => $this->pricingService->formatMoney($subtotalBeforeDiscount),
            'totalDiscount' => $this->pricingService->formatMoney($totalDiscount),
            'totalFinal' => $this->pricingService->formatMoney($totalFinal),
            'total' => $this->pricingService->formatMoney($totalFinal),
            'totalAmount' => $this->pricingService->formatMoney($totalFinal),
            'totalTtc' => $this->pricingService->formatMoney($totalFinal),
            'summaryReductionText' => $summaryReductionText,
            'hasReduction' => $summaryReductionText !== null,
            'imagesBaseUrl' => $imagesBaseUrl,
            'hasStockIssue' => $issuesCount > 0,
            'issuesCount' => $issuesCount,
        ];
    }

    private function findArticleByCode(EntityManagerInterface $em, string $value): ?Article
    {
        $repo = $em->getRepository(Article::class);

        $article = $repo->findOneBy(['id' => $value]);
        if ($article instanceof Article) {
            return $article;
        }

        $article = $repo->find($value);
        if ($article instanceof Article) {
            return $article;
        }

        $article = $repo->findOneBy(['uniqueId' => $value]);
        if ($article instanceof Article) {
            return $article;
        }

        return null;
    }

    private function getPayload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);
        if (is_array($payload)) {
            return $payload;
        }

        return $request->request->all();
    }

    private function resolveImagesBaseUrl(): string
    {
        try {
            $value = (string) $this->getParameter('e_url');
            if ($value !== '') {
                return $value;
            }
        } catch (\Throwable) {
        }

        return (string) ($_ENV['IMAGES_BASE_URL'] ?? getenv('IMAGES_BASE_URL') ?? '/ebpsynch');
    }
}