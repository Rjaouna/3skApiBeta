<?php

namespace App\Controller\Api;

use App\Service\ArticleImageLocator;
use App\Service\MissingArticleImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/missing-images', name: 'api_admin_missing_images_')]
#[IsGranted('ROLE_ADMIN')]
final class MissingArticleImageApiController extends AbstractController
{
    #[Route('/count', name: 'count', methods: ['GET'])]
    public function count(MissingArticleImageService $service): JsonResponse
    {
        return $this->json([
            'ok' => true,
            'count' => count($service->getMissingArticles()),
        ]);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, MissingArticleImageService $service): JsonResponse
    {
        return $this->json([
            'ok' => true,
            ...$service->getPage(
                (int) $request->query->get('page', 1),
                (int) $request->query->get('limit', 50),
                (string) $request->query->get('q', ''),
            ),
        ]);
    }

    #[Route('/{uniqueId}/check', name: 'check', requirements: ['uniqueId' => ArticleImageLocator::UUID_PATTERN], methods: ['POST'])]
    public function check(
        string $uniqueId,
        Request $request,
        MissingArticleImageService $service,
    ): JsonResponse {
        $token = (string) $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('admin-missing-image-check', $token)) {
            return $this->json(['ok' => false, 'message' => 'Requête refusée.'], Response::HTTP_FORBIDDEN);
        }

        $detected = $service->check($uniqueId);
        if ($detected === null) {
            return $this->json(['ok' => false, 'message' => 'Article introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'ok' => true,
            'imageDetected' => $detected,
        ]);
    }
}
