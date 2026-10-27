<?php

namespace App\Controller\Admin;

use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Repository\CustomerOrderRepository;
use App\Exception\ApiReceptionException;
use App\Service\EbpOrderExportService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders', name: 'admin_orders_')]
#[IsGranted('ROLE_ADMIN')]
class AdminOrderController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, CustomerOrderRepository $orderRepository)
    {
        $status = trim((string) $request->query->get('status', ''));
        $query = trim((string) $request->query->get('q', ''));
        $dateFromRaw = trim((string) $request->query->get('dateFrom', ''));
        $dateToRaw = trim((string) $request->query->get('dateTo', ''));
        $city = trim((string) $request->query->get('city', ''));

        $dateFrom = $dateFromRaw !== '' ? \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $dateFromRaw . ' 00:00:00') : null;
        $dateTo = $dateToRaw !== '' ? \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $dateToRaw . ' 23:59:59') : null;

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orderRepository->findAdminList(
                $status !== '' ? $status : null,
                $query !== '' ? $query : null,
                $dateFrom ?: null,
                $dateTo ?: null,
                $city !== '' ? $city : null
            ),
            'currentStatus' => $status,
            'currentQuery' => $query,
            'currentDateFrom' => $dateFromRaw,
            'currentDateTo' => $dateToRaw,
            'currentCity' => $city,
            'labels' => CustomerOrder::getStatusLabels(),
            'statuses' => CustomerOrder::getAvailableStatuses(),
            'totalOrders' => $orderRepository->count([]),
            'newOrdersCount' => $orderRepository->count(['status' => CustomerOrder::STATUS_NEW]),
        ]);
    }

    #[Route('/export-new-to-ebp', name: 'export_new_ebp', methods: ['POST'])]
    public function exportNewOrdersToEbp(Request $request, EbpOrderExportService $exports): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('export_new_orders_ebp', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée (token invalide).');

            return $this->redirectToRoute('admin_orders_index');
        }

        try {
            $result = $exports->exportAllNewOrders();
        } catch (ApiReceptionException $exception) {
            $this->addFlash('warning', 'L’export des commandes vers ApiRecept n’a pas pu démarrer : ' . $exception->getMessage());

            return $this->redirectToRoute('admin_orders_index');
        }

        if ($result['total'] === 0) {
            $this->addFlash('info', 'Aucune commande au statut Nouveau à exporter.');
        } elseif ($result['exported'] > 0) {
            $this->addFlash('success', sprintf(
                '%d commande%s exportée%s vers ApiRecept.',
                $result['exported'],
                $result['exported'] > 1 ? 's' : '',
                $result['exported'] > 1 ? 's' : ''
            ));
        }

        if ($result['failed'] > 0) {
            $this->addFlash('warning', sprintf(
                '%d commande%s n’%s pas encore été acceptée%s par ApiRecept et reste%s au statut Nouveau.',
                $result['failed'],
                $result['failed'] > 1 ? 's' : '',
                $result['failed'] > 1 ? 'ont' : 'a',
                $result['failed'] > 1 ? 's' : '',
                $result['failed'] > 1 ? 'nt' : ''
            ));
        }

        return $this->redirectToRoute('admin_orders_index');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CustomerOrder $order)
    {
        $nextStatus = CustomerOrder::getNextStatus($order->getStatus());

        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
            'nextStatus' => $nextStatus,
            'statusLabels' => CustomerOrder::getStatusLabels(),
        ]);
    }

    #[Route('/{id}/export-ebp', name: 'export_ebp', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function exportToEbp(
        CustomerOrder $order,
        Request $request,
        EbpOrderExportService $exports,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('export_order_ebp_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée (token invalide).');

            return $this->redirectToRoute('admin_orders_show', ['id' => $order->getId()]);
        }

        // This explicit action is useful before cron is configured: it also
        // permits a deliberate re-export after a correction in EBP.
        $exports->queue($order);
        try {
            $exports->dispatch($order);
            $this->addFlash('success', 'Commande exportée vers ApiRecept. Son statut est maintenant « Exportée ». ApiLocal peut créer son CSV EBP.');
        } catch (ApiReceptionException $exception) {
            $this->addFlash('warning', 'Commande mise en attente pour EBP : ' . $exception->getMessage());
        }

        return $this->redirectToRoute('admin_orders_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/export-excel', name: 'export_excel', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function exportExcel(CustomerOrder $order): StreamedResponse
    {
        $filename = sprintf('bon-commande-%s.csv', $this->sanitizeFilename((string) $order->getOrderNumber()));

        $response = new StreamedResponse(function () use ($order): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            $write = static function (array $row) use ($handle): void {
                fputcsv($handle, $row, ';', '"', '');
            };

            $write(['Bon de commande', $order->getOrderNumber()]);
            $write(['Date', $order->getCreatedAt()?->format('d/m/Y H:i') ?? '']);
            $write(['Statut', $order->getStatusLabel()]);
            $write([]);
            $write(['Client', $order->getCompanyName() ?: '']);
            $write(['Email', $order->getCustomerEmail()]);
            $write(['Code client', $order->getCustomerCode() ?: '']);
            $write(['Adresse 1', $order->getAddress1() ?: '']);
            $write(['Adresse 2', $order->getAddress2() ?: '']);
            $write(['Ville', $order->getCity() ?: '']);
            $write([]);
            $write(['Nom de l\'article', 'Reference', 'Qte', 'PU TTC', 'Total TTC']);

            foreach ($order->getLines() as $line) {
                $write([
                    $line->getDesignation() ?: '',
                    $line->getArticleCode(),
                    $line->getQuantity(),
                    $line->getUnitPriceTtc(),
                    $line->getLineTotalTtc(),
                ]);
            }

            $write([]);
            $write(['Sous-total TTC', '', '', '', $order->getSubtotalTtc()]);

            if ((float) $order->getDiscountAmountTtc() > 0) {
                $write(['Remise TTC', '', '', '', '-' . $order->getDiscountAmountTtc()]);
            }

            $write(['Total TTC', '', '', '', $order->getTotalTtc()]);

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    #[Route('/{id}/print', name: 'print', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function print(CustomerOrder $order): Response
    {
        return $this->render('admin/order/print.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        CustomerOrder $order,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('delete_order_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token invalide.');

            return $this->redirectToRoute('admin_orders_index');
        }

        if (!$this->isAdminPasswordConfirmed($request, $passwordHasher)) {
            $this->addFlash('danger', 'Mot de passe administrateur incorrect.');

            return $this->redirectToRoute('admin_orders_index');
        }

        $orderNumber = $order->getOrderNumber();
        $em->remove($order);
        $em->flush();

        $this->addFlash('success', sprintf('La commande %s a ete supprimee.', $orderNumber));

        return $this->redirectToRoute('admin_orders_index');
    }

    #[Route('/delete-all', name: 'delete_all', methods: ['POST'])]
    public function deleteAll(
        Request $request,
        CustomerOrderRepository $orderRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('delete_all_orders', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token invalide.');

            return $this->redirectToRoute('admin_orders_index');
        }

        if (!$this->isAdminPasswordConfirmed($request, $passwordHasher)) {
            $this->addFlash('danger', 'Mot de passe administrateur incorrect.');

            return $this->redirectToRoute('admin_orders_index');
        }

        $orders = $orderRepository->findAll();
        $deletedCount = count($orders);

        foreach ($orders as $order) {
            $em->remove($order);
        }

        $em->flush();

        $this->addFlash('success', sprintf('%d commande%s supprimee%s.', $deletedCount, $deletedCount > 1 ? 's' : '', $deletedCount > 1 ? 's' : ''));

        return $this->redirectToRoute('admin_orders_index');
    }

    #[Route('/{id}/advance', name: 'advance', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function advance(
        CustomerOrder $order,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('advance_order_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token invalide.');
            return $this->redirectToRoute('admin_orders_index');
        }

        $oldStatus = $order->getStatus();
        $next = CustomerOrder::getNextStatus($oldStatus);

        if ($next === null) {
            $this->addFlash('warning', 'Aucun statut suivant disponible.');
            return $this->redirectToRoute('admin_orders_show', ['id' => $order->getId()]);
        }

        $order->setStatus($next);
        $em->flush();

        $actor = $this->getUser();
        $notificationService->notifyOrderStatusChanged(
            $order,
            $oldStatus,
            $next,
            $actor instanceof User ? $actor : null
        );

        $this->addFlash('success', 'Statut de la commande mis à jour.');
        return $this->redirectToRoute('admin_orders_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(
        CustomerOrder $order,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('cancel_order_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token invalide.');
            return $this->redirectToRoute('admin_orders_index');
        }

        if (in_array($order->getStatus(), [CustomerOrder::STATUS_COMPLETED, CustomerOrder::STATUS_CANCELLED], true)) {
            $this->addFlash('warning', 'Cette commande ne peut plus être annulée.');
            return $this->redirectToRoute('admin_orders_show', ['id' => $order->getId()]);
        }

        $oldStatus = $order->getStatus();
        $order->setStatus(CustomerOrder::STATUS_CANCELLED);
        $em->flush();

        $actor = $this->getUser();
        $notificationService->notifyOrderStatusChanged(
            $order,
            $oldStatus,
            CustomerOrder::STATUS_CANCELLED,
            $actor instanceof User ? $actor : null
        );

        $this->addFlash('success', 'Commande annulée.');
        return $this->redirectToRoute('admin_orders_show', ['id' => $order->getId()]);
    }

    private function sanitizeFilename(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: 'commande';

        return trim($value, '-_.') ?: 'commande';
    }

    private function isAdminPasswordConfirmed(Request $request, UserPasswordHasherInterface $passwordHasher): bool
    {
        $user = $this->getUser();
        $password = (string) $request->request->get('confirm_password', '');

        if (!$user instanceof User || trim($password) === '') {
            return false;
        }

        return $passwordHasher->isPasswordValid($user, $password);
    }
}
