<?php

namespace App\Controller\Admin;

use App\Entity\EbpCustomerImport;
use App\Exception\ApiReceptionException;
use App\Repository\EbpCustomerImportRepository;
use App\Service\EbpCustomerImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/clients/ebp-imports', name: 'admin_clients_ebp_import_')]
#[IsGranted('ROLE_ADMIN')]
final class EbpCustomerImportController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(EbpCustomerImportRepository $imports): JsonResponse
    {
        return $this->json([
            'ok' => true,
            'items' => array_map(static fn (EbpCustomerImport $import) => $import->toArray(), $imports->latest()),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    #[Route('', name: 'request', methods: ['POST'])]
    public function request(Request $request, EbpCustomerImportService $imports): JsonResponse
    {
        if (!$this->csrf($request)) {
            return $this->forbidden();
        }
        try {
            $item = $imports->request((string) ($this->body($request)['uniqueId'] ?? ''));
        } catch (\DomainException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 422, ['Cache-Control' => 'no-store']);
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 502, ['Cache-Control' => 'no-store']);
        }

        return $this->json(['ok' => true, 'item' => $item, 'message' => 'Demande envoyée à ApiRecept. ApiLocal créera ou mettra à jour la fiche automatiquement.'], 201, ['Cache-Control' => 'no-store']);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request, EbpCustomerImportService $imports, EbpCustomerImportRepository $repository): JsonResponse
    {
        if (!$this->csrf($request)) {
            return $this->forbidden();
        }
        try {
            $summary = $imports->refresh();
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 502, ['Cache-Control' => 'no-store']);
        }

        return $this->json([
            'ok' => true,
            'summary' => $summary,
            'items' => array_map(static fn (EbpCustomerImport $import) => $import->toArray(), $repository->latest()),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, EbpCustomerImportRepository $repository, EbpCustomerImportService $imports): JsonResponse
    {
        if (!$this->csrf($request)) {
            return $this->forbidden();
        }
        $item = $repository->find($id);
        if (!$item instanceof EbpCustomerImport) {
            return $this->json(['ok' => false, 'message' => 'Demande d’import introuvable.'], 404, ['Cache-Control' => 'no-store']);
        }
        try {
            $imports->delete($item);
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 502, ['Cache-Control' => 'no-store']);
        }

        return $this->json(['ok' => true, 'message' => 'Demande supprimée.'], 200, ['Cache-Control' => 'no-store']);
    }

    #[Route('/clear/all', name: 'clear', methods: ['POST'])]
    public function clear(Request $request, EbpCustomerImportService $imports): JsonResponse
    {
        if (!$this->csrf($request)) {
            return $this->forbidden();
        }
        try {
            $cleared = $imports->clear();
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 502, ['Cache-Control' => 'no-store']);
        }

        return $this->json(['ok' => true, 'cleared' => $cleared, 'message' => 'Liste des demandes vidée.'], 200, ['Cache-Control' => 'no-store']);
    }

    /** @return array<string, mixed> */
    private function body(Request $request): array
    {
        $value = json_decode((string) $request->getContent(), true);

        return is_array($value) ? $value : [];
    }

    private function csrf(Request $request): bool
    {
        return $this->isCsrfTokenValid('admin_clients_crud', (string) ($this->body($request)['_token'] ?? ''));
    }

    private function forbidden(): JsonResponse
    {
        return $this->json(['ok' => false, 'message' => 'Requête refusée. Rechargez la page.'], 403, ['Cache-Control' => 'no-store']);
    }
}
