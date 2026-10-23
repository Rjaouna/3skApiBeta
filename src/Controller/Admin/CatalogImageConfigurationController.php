<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Exception\ApiReceptionException;
use App\Repository\ArticleRepository;
use App\Service\CatalogImageBaseUrlProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/admin/catalog-images', name: 'admin_catalog_images_')]
#[IsGranted('ROLE_ADMIN')]
final class CatalogImageConfigurationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CatalogImageBaseUrlProvider $images, ArticleRepository $articles): Response
    {
        return $this->render('admin/catalog_images/index.html.twig', [
            'configuration' => $images->getConfiguration(),
            'fallbackBaseUrl' => $images->baseUrl(),
            'samples' => $this->samples($articles, $images, $images->baseUrl()),
            'saveToken' => $this->container->get('security.csrf.token_manager')->getToken('catalog_images_save')->getValue(),
            'testToken' => $this->container->get('security.csrf.token_manager')->getToken('catalog_images_test')->getValue(),
            'purgeToken' => $this->container->get('security.csrf.token_manager')->getToken('catalog_images_purge')->getValue(),
        ]);
    }

    #[Route('/save', name: 'save', methods: ['POST'])]
    public function save(Request $request, CatalogImageBaseUrlProvider $images, EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = $this->payload($request);
        if (!$this->isCsrfTokenValid('catalog_images_save', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Jeton de sécurité invalide. Actualisez la page puis réessayez.'], 403);
        }

        try {
            $configuration = $images->getConfiguration();
            $configuration->setBaseUrl($this->normalizeBaseUrl((string) ($payload['baseUrl'] ?? '')))->touch();
            $entityManager->flush();

            return $this->json(['ok' => true, 'message' => 'Source des images enregistrée.', 'baseUrl' => $images->baseUrl()]);
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    #[Route('/test', name: 'test', methods: ['POST'])]
    public function test(
        Request $request,
        CatalogImageBaseUrlProvider $images,
        ArticleRepository $articles,
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $payload = $this->payload($request);
        if (!$this->isCsrfTokenValid('catalog_images_test', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Jeton de sécurité invalide. Actualisez la page puis réessayez.'], 403);
        }

        try {
            $baseUrl = $this->normalizeBaseUrl((string) ($payload['baseUrl'] ?? ''));
            if ($baseUrl === null) {
                $baseUrl = $images->baseUrl();
            }
            $samples = $this->samples($articles, $images, $baseUrl);
            if ($samples === []) {
                throw new ApiReceptionException('Aucun article avec un identifiant image n’est encore disponible pour le test.');
            }

            $reachable = 0;
            foreach ($samples as &$sample) {
                $url = $this->absoluteUrl($request, $sample['url']);
                try {
                    $response = $httpClient->request('GET', $url, ['timeout' => 8, 'max_redirects' => 0, 'buffer' => false]);
                    $status = $response->getStatusCode();
                    $response->cancel();
                    $sample['status'] = $status;
                    $sample['available'] = $status >= 200 && $status < 300;
                    if ($sample['available']) {
                        ++$reachable;
                    }
                } catch (ExceptionInterface) {
                    $sample['status'] = null;
                    $sample['available'] = false;
                }
            }
            unset($sample);
            if ($reachable === 0) {
                throw new ApiReceptionException('Aucune image de test ne répond. Vérifiez l’URL publique, le chemin et la publication FTPS.');
            }

            $configuration = $images->getConfiguration();
            if ($configuration->getBaseUrl() === $baseUrl) {
                $configuration->markTested();
                $entityManager->flush();
            }

            return $this->json(['ok' => true, 'message' => sprintf('%d image(s) sur %d répondent.', $reachable, count($samples)), 'baseUrl' => $baseUrl, 'samples' => $samples]);
        } catch (ApiReceptionException $exception) {
            $configuration = $images->getConfiguration();
            if ($configuration->getBaseUrl() === trim((string) ($payload['baseUrl'] ?? ''))) {
                $configuration->markError($exception->getMessage());
                $entityManager->flush();
            }
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    #[Route('/purge-cache', name: 'purge', methods: ['POST'])]
    public function purge(Request $request, CatalogImageBaseUrlProvider $images, ArticleRepository $articles): JsonResponse
    {
        $payload = $this->payload($request);
        if (!$this->isCsrfTokenValid('catalog_images_purge', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Jeton de sécurité invalide. Actualisez la page puis réessayez.'], 403);
        }

        try {
            $images->purgeCache();

            return $this->json([
                'ok' => true,
                'message' => 'Cache des photos purgé. Toutes les photos seront rechargées à la prochaine ouverture ou actualisation du catalogue. Les fichiers sont conservés.',
                'baseUrl' => $images->baseUrl(),
                'samples' => $this->samples($articles, $images, $images->baseUrl()),
            ], headers: ['Cache-Control' => 'no-store']);
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    /** @return list<array{uniqueId: string, label: string, url: string, available?: bool, status?: ?int}> */
    private function samples(ArticleRepository $articles, CatalogImageBaseUrlProvider $images, string $baseUrl): array
    {
        $items = [];
        foreach ($articles->findBy([], ['sysModifiedDate' => 'DESC'], 4) as $article) {
            if (!$article instanceof Article || !is_string($article->getUniqueId()) || $article->getUniqueId() === '') {
                continue;
            }
            $items[] = [
                'uniqueId' => $article->getUniqueId(),
                'label' => trim((string) $article->getId()) ?: $article->getUniqueId(),
                'url' => $images->imageUrl($article->getUniqueId(), $baseUrl),
            ];
        }

        return $items;
    }

    private function normalizeBaseUrl(string $value): ?string
    {
        $value = rtrim(trim($value), '/');
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, '/')) {
            if (str_starts_with($value, '//') || str_contains($value, '?') || str_contains($value, '#') || preg_match('/[\x00-\x1F]/', $value)) {
                throw new ApiReceptionException('Le chemin local des images est invalide.');
            }

            return $value;
        }
        $parts = parse_url($value);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])) {
            throw new ApiReceptionException('Indiquez une URL publique complète, par exemple https://cdn.exemple.fr/images.');
        }
        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if ($scheme !== 'https' && !($scheme === 'http' && $isLocal)) {
            throw new ApiReceptionException('Utilisez HTTPS pour la source publique des images. HTTP est accepté uniquement en local.');
        }

        return $value;
    }

    private function absoluteUrl(Request $request, string $url): string
    {
        return str_starts_with($url, '/') ? $request->getSchemeAndHttpHost() . $url : $url;
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        try { $payload = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { $payload = null; }
        return is_array($payload) ? $payload : $request->request->all();
    }
}
