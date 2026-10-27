<?php

namespace App\Controller\Admin;

use App\Entity\ApiReceptionConnection;
use App\Exception\ApiReceptionException;
use App\Service\ApiReceptionClient;
use App\Service\ApiReceptionConnectionProvider;
use App\Service\ApiReceptionCredentialCipher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/api-reception', name: 'admin_api_reception_')]
#[IsGranted('ROLE_ADMIN')]
final class ApiReceptionConfigurationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ApiReceptionConnectionProvider $connections): Response
    {
        return $this->render('admin/api_reception/index.html.twig', [
            'connection' => $connections->get(),
            'saveToken' => $this->container->get('security.csrf.token_manager')->getToken('api_reception_save')->getValue(),
            'testToken' => $this->container->get('security.csrf.token_manager')->getToken('api_reception_test')->getValue(),
        ]);
    }

    #[Route('/save', name: 'save', methods: ['POST'])]
    public function save(
        Request $request,
        ApiReceptionConnectionProvider $connections,
        ApiReceptionCredentialCipher $cipher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $payload = $this->payload($request);
        if (!$this->isCsrfTokenValid('api_reception_save', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Jeton de sécurité invalide. Actualisez la page puis réessayez.'], 403);
        }

        try {
            $baseUrl = $this->normalizeBaseUrl((string) ($payload['baseUrl'] ?? ''));
            $interval = filter_var($payload['syncIntervalMinutes'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 1440],
            ]);
            if ($interval === false) {
                throw new ApiReceptionException('La fréquence doit être comprise entre 1 minute et 24 heures.');
            }

            $connection = $connections->get();
            $apiKey = trim((string) ($payload['apiKey'] ?? ''));
            $removeApiKey = $this->boolean($payload['removeApiKey'] ?? false);
            // A new endpoint or a replaced credential may point to another
            // company. Its revision cursor is therefore never reusable.
            $resetCatalogCursor = $connection->getBaseUrl() !== $baseUrl || $apiKey !== '' || $removeApiKey;
            if ($removeApiKey && $apiKey !== '') {
                throw new ApiReceptionException('Saisissez une nouvelle clé ou demandez sa suppression, mais pas les deux à la fois.');
            }
            if ($apiKey !== '') {
                if (!preg_match('/^[A-Za-z0-9_-]{16,255}$/D', $apiKey)) {
                    throw new ApiReceptionException('Le format de la clé API est invalide. Copiez la clé complète générée par ApiRecept.');
                }
                $connection->setApiKeyCiphertext($cipher->encrypt($apiKey));
            } elseif ($removeApiKey) {
                $connection->clearApiKey();
            }

            $enabled = $this->boolean($payload['enabled'] ?? false);
            if ($enabled && !$connection->hasApiKey()) {
                throw new ApiReceptionException('Enregistrez une clé API avant d’activer la synchronisation.');
            }

            $connection
                ->setBaseUrl($baseUrl)
                ->setSyncIntervalMinutes((int) $interval)
                ->setEnabled($enabled)
                ->touch();
            if ($resetCatalogCursor) {
                $connection->resetCatalogRevision();
            }

            $entityManager->flush();

            return $this->json([
                'ok' => true,
                'message' => 'Connexion ApiRecept enregistrée.',
                'connection' => $this->connectionPayload($connection),
            ]);
        } catch (ApiReceptionException $exception) {
            return $this->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    #[Route('/test', name: 'test', methods: ['POST'])]
    public function test(
        Request $request,
        ApiReceptionConnectionProvider $connections,
        ApiReceptionClient $client,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $payload = $this->payload($request);
        if (!$this->isCsrfTokenValid('api_reception_test', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Jeton de sécurité invalide. Actualisez la page puis réessayez.'], 403);
        }

        $connection = $connections->get();
        try {
            if ($connection->getBaseUrl() === null || !$connection->hasApiKey()) {
                throw new ApiReceptionException('Enregistrez d’abord l’URL et la clé API.');
            }

            $result = $client->test($connection);
            $connection->markTested();
            $entityManager->flush();

            return $this->json([
                'ok' => true,
                'message' => sprintf('Connexion ApiRecept validée en %d ms.', $result['latencyMs']),
                'remoteTime' => $result['remoteTime'],
                'connection' => $this->connectionPayload($connection),
            ]);
        } catch (ApiReceptionException $exception) {
            $connection->markError($exception->getMessage());
            $entityManager->flush();

            return $this->json(['ok' => false, 'message' => $exception->getMessage(), 'connection' => $this->connectionPayload($connection)], 422);
        }
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        try {
            $payload = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $payload = null;
        }

        return is_array($payload) ? $payload : $request->request->all();
    }

    private function normalizeBaseUrl(string $value): string
    {
        $value = rtrim(trim($value), '/');
        $parts = parse_url($value);
        if ($value === '' || $parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new ApiReceptionException('Indiquez l’URL complète de l’API, par exemple https://api.exemple.fr.');
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new ApiReceptionException('L’URL ApiRecept ne doit contenir ni identifiants, ni paramètres, ni fragment.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        $isLocalDevelopment = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if ($scheme !== 'https' && !($scheme === 'http' && $isLocalDevelopment)) {
            throw new ApiReceptionException('ApiRecept doit utiliser HTTPS. HTTP est accepté uniquement pour un test local.');
        }

        return $value;
    }

    private function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, mixed> */
    private function connectionPayload(ApiReceptionConnection $connection): array
    {
        return [
            'baseUrl' => $connection->getBaseUrl(),
            'enabled' => $connection->isEnabled(),
            'apiKeyConfigured' => $connection->hasApiKey(),
            'syncIntervalMinutes' => $connection->getSyncIntervalMinutes(),
            'lastTestedAt' => $connection->getLastTestedAt()?->format(DATE_ATOM),
            'lastSyncedAt' => $connection->getLastSyncedAt()?->format(DATE_ATOM),
            'lastError' => $connection->getLastError(),
        ];
    }
}
