<?php

namespace App\Service;

use App\Entity\ApiReceptionConnection;
use App\Exception\ApiReceptionException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiReceptionClient
{
    public const PAGE_SIZE = 100;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ApiReceptionCredentialCipher $cipher,
    ) {
    }

    /** @return array{latencyMs: int, remoteTime: ?string} */
    public function test(ApiReceptionConnection $connection): array
    {
        $apiKey = $this->apiKey($connection);
        $startedAt = hrtime(true);
        $health = $this->getJson($connection, '/healthz');
        if (($health['status'] ?? null) !== 'ok' || ($health['service'] ?? null) !== 'api-recept') {
            throw new ApiReceptionException('L’URL indiquée ne correspond pas à une instance ApiRecept valide.');
        }
        $this->getJson($connection, '/api/v1/records/items?page=1&pageSize=1', $apiKey);

        return [
            'latencyMs' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'remoteTime' => is_string($health['time'] ?? null) ? $health['time'] : null,
        ];
    }

    /** @return array{total: int, items: list<array<string, mixed>>} */
    public function itemsPage(ApiReceptionConnection $connection, int $page): array
    {
        return $this->recordsPage($connection, 'items', $page, 'articles');
    }

    /**
     * @return array<string, string> association ItemFamily.Id => ItemFamily.Caption
     */
    public function itemFamilyNames(ApiReceptionConnection $connection): array
    {
        $names = [];
        $expectedTotal = null;
        $receivedRows = 0;

        for ($page = 1; ; ++$page) {
            $remote = $this->recordsPage($connection, 'item_families', $page, 'familles d’articles');
            if ($expectedTotal === null) {
                $expectedTotal = $remote['total'];
            } elseif ($expectedTotal !== $remote['total']) {
                throw new ApiReceptionException('Les familles d’articles ApiRecept ont changé pendant la synchronisation. Réessayez dans quelques instants.');
            }

            if ($remote['items'] === []) {
                if ($receivedRows !== $expectedTotal) {
                    throw new ApiReceptionException('ApiRecept a retourné une pagination incomplète pour les familles d’articles.');
                }

                break;
            }

            foreach ($remote['items'] as $record) {
                $data = $record['data'] ?? null;
                if (!is_array($data) || array_is_list($data)) {
                    throw new ApiReceptionException('ApiRecept a retourné une famille d’article invalide.');
                }

                $id = $this->recordText($data['Id'] ?? null, 191);
                $caption = $this->recordText($data['Caption'] ?? null, 100);
                if ($id === '' || $caption === '') {
                    throw new ApiReceptionException('Le profil Familles doit exporter les champs « Id » et « Caption » de dbo.ItemFamily.');
                }
                $this->indexFamilyName($names, $id, $caption);
            }

            $receivedRows += count($remote['items']);
            if ($receivedRows > $expectedTotal) {
                throw new ApiReceptionException('ApiRecept a retourné plus de familles d’articles que prévu.');
            }
            if ($receivedRows === $expectedTotal) {
                break;
            }
        }

        return $names;
    }

    public function requestPhotoRefresh(ApiReceptionConnection $connection, string $uniqueId): array
    {
        return $this->requestJson($connection, '/api/v1/photo-requests', $this->apiKey($connection), 'POST', ['uniqueId' => $uniqueId]);
    }

    public function photoPublicationRevision(ApiReceptionConnection $connection): string
    {
        $data = $this->requestJson($connection, '/api/v1/photo-requests/revision', $this->apiKey($connection), timeout: 2);
        $revision = $data['revision'] ?? null;
        if (!is_string($revision) || preg_match('/^(initial|[a-f0-9]{32})$/D', $revision) !== 1) {
            throw new ApiReceptionException('La version des photos ApiRecept est invalide.');
        }

        return $revision;
    }

    private function apiKey(ApiReceptionConnection $connection): string
    {
        if (!$connection->hasApiKey()) {
            throw new ApiReceptionException('Aucune clé API n’est enregistrée. Configurez la connexion à ApiRecept.');
        }

        return $this->cipher->decrypt((string) $connection->getApiKeyCiphertext());
    }

    /** @return array{total: int, items: list<array<string, mixed>>} */
    private function recordsPage(ApiReceptionConnection $connection, string $resource, int $page, string $label): array
    {
        $payload = $this->getJson(
            $connection,
            sprintf('/api/v1/records/%s?page=%d&pageSize=%d', $resource, $page, self::PAGE_SIZE),
            $this->apiKey($connection),
        );

        if (($payload['resource'] ?? null) !== $resource || !is_int($payload['total'] ?? null) || !is_array($payload['items'] ?? null)) {
            throw new ApiReceptionException(sprintf('La réponse de l’API ne respecte pas le format attendu pour les %s.', $label));
        }

        foreach ($payload['items'] as $item) {
            if (!is_array($item) || array_is_list($item)) {
                throw new ApiReceptionException(sprintf('L’API a retourné un enregistrement de %s invalide.', $label));
            }
        }

        /** @var list<array<string, mixed>> $items */
        $items = $payload['items'];

        return ['total' => $payload['total'], 'items' => $items];
    }

    private function recordText(mixed $value, int $limit): string
    {
        if (!is_scalar($value) && $value !== null) {
            return '';
        }

        return mb_substr(trim((string) $value), 0, $limit, 'UTF-8');
    }

    /** @param array<string, string> $names */
    private function indexFamilyName(array &$names, string $id, string $caption): void
    {
        if (isset($names[$id]) && $names[$id] !== $caption) {
            throw new ApiReceptionException(sprintf('ApiRecept a retourné deux libellés différents pour la famille « %s ».', $id));
        }
        $names[$id] = $caption;

        $numericAlias = ApiCatalogFamilyKey::numericAlias($id);
        if ($numericAlias === null) {
            return;
        }
        if (isset($names[$numericAlias]) && $names[$numericAlias] !== $caption) {
            throw new ApiReceptionException(sprintf('Les familles EBP utilisent des identifiants numériques ambigus autour de « %s ».', $id));
        }

        $names[$numericAlias] = $caption;
    }

    /** @return array<string, mixed> */
    private function getJson(ApiReceptionConnection $connection, string $path, ?string $apiKey = null): array
    {
        return $this->requestJson($connection, $path, $apiKey);
    }

    private function requestJson(ApiReceptionConnection $connection, string $path, ?string $apiKey = null, string $method = 'GET', ?array $body = null, float $timeout = 20): array
    {
        $url = $this->url($connection, $path);
        $headers = ['Accept' => 'application/json'];
        if ($apiKey !== null) {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        try {
            $response = $this->httpClient->request($method, $url, [
                'headers' => $headers,
                'timeout' => $timeout,
                ...($method === 'POST' || $timeout < 20 ? ['max_duration' => $timeout] : []),
                'max_redirects' => 0,
                ...($body === null ? [] : ['json' => $body]),
            ]);
            $statusCode = $response->getStatusCode();
            if (!in_array($statusCode, $method === 'POST' ? [200, 201] : [200], true)) {
                if ($statusCode === 401 || $statusCode === 403) {
                    throw new ApiReceptionException('ApiRecept a refusé la clé API enregistrée.');
                }
                throw new ApiReceptionException(sprintf('ApiRecept a répondu avec le statut HTTP %d.', $statusCode));
            }

            $payload = $response->toArray(false);
        } catch (ApiReceptionException $exception) {
            throw $exception;
        } catch (ExceptionInterface|\JsonException $exception) {
            throw new ApiReceptionException('Impossible de joindre ApiRecept. Vérifiez l’URL, le certificat HTTPS et le réseau.', 0, $exception);
        }

        if (!is_array($payload) || array_is_list($payload)) {
            throw new ApiReceptionException('ApiRecept a retourné une réponse JSON invalide.');
        }

        return $payload;
    }

    private function url(ApiReceptionConnection $connection, string $path): string
    {
        $baseUrl = trim((string) $connection->getBaseUrl());
        if ($baseUrl === '') {
            throw new ApiReceptionException('L’URL ApiRecept n’est pas configurée.');
        }

        return rtrim($baseUrl, '/') . $path;
    }
}
