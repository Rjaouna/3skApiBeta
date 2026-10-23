<?php

namespace App\Controller\Admin;

use App\Exception\ApiReceptionException;
use App\Repository\ArticleRepository;
use App\Repository\ApiCatalogSyncRunRepository;
use App\Service\ApiCatalogSynchronizer;
use App\Service\ApiCatalogSyncProgress;
use App\Service\ApiReceptionClient;
use App\Service\ApiReceptionConnectionProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/api-catalog-sync', name: 'admin_api_catalog_sync_')]
#[IsGranted('ROLE_ADMIN')]
final class ApiCatalogSyncController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        ApiReceptionConnectionProvider $connections,
        ApiCatalogSyncRunRepository $runs,
        ArticleRepository $articles,
    ): Response {
        $lastRun = $runs->findLatest();

        return $this->render('admin/api_catalog_sync/index.html.twig', [
            'connection' => $connections->get(),
            'lastRun' => $lastRun?->toSummary(),
            'localArticleCount' => $articles->count([]),
            'runToken' => $this->container->get('security.csrf.token_manager')->getToken('api_catalog_sync_run')->getValue(),
        ]);
    }

    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(
        ApiCatalogSyncRunRepository $runs,
        ArticleRepository $articles,
        EntityManagerInterface $entityManager,
        ApiCatalogSyncProgress $liveProgress,
    ): JsonResponse
    {
        $run = $runs->findLatest();
        $live = $run !== null ? $liveProgress->read($run) : [];
        $lastActivity = isset($live['updatedAt']) ? new \DateTimeImmutable($live['updatedAt']) : null;
        if ($run?->isStale(15 * 60) && ($lastActivity === null || $lastActivity <= new \DateTimeImmutable('-15 minutes'))) {
            $run->fail('La synchronisation précédente a été interrompue avant sa fin. Vous pouvez la relancer.');
            $entityManager->flush();
            $liveProgress->finish($run);
            $live = $liveProgress->read($run);
        }
        $summary = $run?->toSummary();
        $remoteTotal = (int) ($summary['remoteTotal'] ?? 0);
        $receivedRows = (int) ($summary['receivedRows'] ?? 0);
        $isRunning = ($summary['status'] ?? null) === 'RUNNING';

        return $this->json([
            'run' => $summary,
            'localArticleCount' => $articles->count([]),
            'progress' => [
                'percent' => $remoteTotal > 0 ? min(100, (int) floor(($receivedRows / $remoteTotal) * 100)) : (($summary['status'] ?? null) === 'DONE' ? 100 : 0),
                'remainingRows' => max(0, $remoteTotal - $receivedRows),
                'currentPage' => $live['currentPage'] ?? ($receivedRows > 0 ? (int) ceil($receivedRows / ApiReceptionClient::PAGE_SIZE) : 0),
                'totalPages' => $remoteTotal > 0 ? (int) ceil($remoteTotal / ApiReceptionClient::PAGE_SIZE) : 0,
                'totalKnown' => $live['totalKnown'] ?? $remoteTotal > 0,
                'currentArticle' => $live['currentArticle'] ?? null,
                'updatedAt' => $live['updatedAt'] ?? null,
                'phase' => $isRunning ? ($live['phase'] ?? ($remoteTotal > 0 && $receivedRows >= $remoteTotal
                    ? 'Finalisation des données locales'
                    : 'Réception et mise à jour des articles')) : null,
            ],
            'logs' => $live['logs'] ?? [],
            'observedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], headers: ['Cache-Control' => 'no-store']);
    }

    #[Route('/run', name: 'run', methods: ['POST'])]
    public function run(Request $request, ApiCatalogSynchronizer $synchronizer): JsonResponse
    {
        try {
            $payload = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $payload = null;
        }
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        if (!$this->isCsrfTokenValid('api_catalog_sync_run', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Jeton de sécurité invalide. Actualisez la page puis réessayez.'], 403);
        }

        // La synchronisation peut prendre plusieurs minutes. Libère le verrou de session
        // afin que les requêtes /status du même administrateur puissent lire l'avancement.
        if ($request->hasSession()) {
            $request->getSession()->save();
        }

        try {
            $run = $synchronizer->synchronize();

            return $this->json([
                'ok' => true,
                'message' => (string) $run->toSummary()['message'],
                'run' => $run->toSummary(),
            ]);
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
