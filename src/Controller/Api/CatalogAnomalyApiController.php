<?php

namespace App\Controller\Api;

use App\Service\ArticleImageLocator;
use App\Service\CatalogAnomalyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/catalog-anomalies', name: 'api_admin_catalog_anomalies_')]
#[IsGranted('ROLE_ADMIN')]
final class CatalogAnomalyApiController extends AbstractController
{
    private const ACTION_TOKEN = 'admin-catalog-anomalies-action';

    #[Route('/summary', name: 'summary', methods: ['GET'])]
    public function summary(CatalogAnomalyService $service): JsonResponse
    {
        return $this->json(['ok' => true, 'summary' => $service->getSummary()]);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, CatalogAnomalyService $service): JsonResponse
    {
        $type = (string) $request->query->get('type', CatalogAnomalyService::TYPE_MISSING_IMAGE);
        if (!$service->supportsType($type)) {
            return $this->json(['ok' => false, 'message' => 'Type d’anomalie invalide.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'ok' => true,
            'summary' => $service->getSummary(),
            ...$service->getPage(
                $type,
                (int) $request->query->get('page', 1),
                (int) $request->query->get('limit', 50),
                (string) $request->query->get('q', ''),
            ),
        ]);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request, CatalogAnomalyService $service): JsonResponse
    {
        if (!$this->isCsrfTokenValid(self::ACTION_TOKEN, (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['ok' => false, 'message' => 'Requête refusée.'], Response::HTTP_FORBIDDEN);
        }

        return $this->json(['ok' => true, 'summary' => $service->refresh()]);
    }

    #[Route('/{uniqueId}/check-image', name: 'check_image', requirements: ['uniqueId' => ArticleImageLocator::UUID_PATTERN], methods: ['POST'])]
    public function checkImage(
        string $uniqueId,
        Request $request,
        CatalogAnomalyService $service,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid(self::ACTION_TOKEN, (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['ok' => false, 'message' => 'Requête refusée.'], Response::HTTP_FORBIDDEN);
        }

        $result = $service->checkImage($uniqueId);
        if ($result === null) {
            return $this->json(['ok' => false, 'message' => 'Article introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['ok' => true, ...$result]);
    }
}
