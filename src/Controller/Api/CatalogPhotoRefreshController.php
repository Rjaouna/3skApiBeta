<?php

namespace App\Controller\Api;

use App\Exception\ApiReceptionException;
use App\Repository\ArticleRepository;
use App\Service\ApiReceptionClient;
use App\Service\ApiReceptionConnectionProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class CatalogPhotoRefreshController extends AbstractController
{
    #[Route('/api/admin/catalog-photos/{uniqueId}/request', name: 'api_admin_catalog_photo_request', requirements: ['uniqueId' => '[0-9a-fA-F-]{36}'], methods: ['POST'])]
    public function requestPhoto(string $uniqueId, Request $request, ArticleRepository $articles, ApiReceptionConnectionProvider $connections, ApiReceptionClient $client): JsonResponse
    {
        if (!$this->isCsrfTokenValid('catalog-photo-refresh', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse(['ok' => false, 'message' => 'Requête refusée. Rechargez la page.'], 403, ['Cache-Control' => 'no-store']);
        }
        $article = $articles->findOneBy(['uniqueId' => $uniqueId]);
        if ($article === null) {
            return new JsonResponse(['ok' => false, 'message' => 'Article introuvable.'], 404, ['Cache-Control' => 'no-store']);
        }
        try {
            $result = $client->requestPhotoRefresh($connections->get(), (string) $article->getUniqueId());
            if (($result['ok'] ?? false) !== true || strtoupper((string) ($result['item']['uniqueId'] ?? '')) !== strtoupper($uniqueId)) {
                throw new ApiReceptionException('ApiRecept n’a pas confirmé cet article.');
            }
        } catch (ApiReceptionException $exception) {
            return new JsonResponse(['ok' => false, 'message' => $exception->getMessage()], 502, ['Cache-Control' => 'no-store']);
        }

        return new JsonResponse(['ok' => true, 'message' => 'Demande enregistrée. Lancez « MAJ photos manquantes » dans ApiLocal.'], 200, ['Cache-Control' => 'no-store']);
    }
}
