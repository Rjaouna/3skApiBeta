<?php

namespace App\Controller\Admin;

use App\Exception\ApiReceptionException;
use App\Service\EbpInvoiceImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/clients/invoices', name: 'admin_clients_invoices_')]
#[IsGranted('ROLE_ADMIN')]
final class EbpInvoiceImportController extends AbstractController
{
    #[Route('/sync-all', name: 'sync_all', methods: ['POST'])]
    public function syncAll(Request $request, EbpInvoiceImportService $imports): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!$this->isCsrfTokenValid('admin_clients_crud', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Requête refusée. Rechargez la page.'], 403, ['Cache-Control' => 'no-store']);
        }
        try {
            $queued = $imports->requestAll();
            $refresh = $imports->refresh();
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 502, ['Cache-Control' => 'no-store']);
        }

        return $this->json([
            'ok' => true,
            'queued' => $queued,
            'refresh' => $refresh,
            'message' => sprintf('%d client(s) EBP ont été ajoutés à la file de chargement des documents.', $queued['requested']),
        ], 202, ['Cache-Control' => 'no-store']);
    }
}
