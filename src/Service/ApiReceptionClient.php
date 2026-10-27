<?php

namespace App\Service;

use App\Entity\ApiReceptionConnection;
use App\Exception\ApiReceptionException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiReceptionClient
{
    public const PAGE_SIZE = 100;

    /** @var list<int> */
    private const TRANSIENT_RETRY_DELAYS_MS = [500, 1000, 2000];

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ApiReceptionCredentialCipher $cipher,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
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
    /** @param null|callable(int, int, int): void $onRetry */
    public function itemsPage(ApiReceptionConnection $connection, int $page, ?callable $onRetry = null): array
    {
        return $this->recordsPage($connection, 'items', $page, 'articles', $onRetry);
    }

    /**
     * Opens a stable revision window before the initial catalogue import.
     * Changes committed after this point will be replayed incrementally.
     */
    /** @param null|callable(int, int, int): void $onRetry */
    public function catalogHead(ApiReceptionConnection $connection, ?callable $onRetry = null): ?int
    {
        try {
            return $this->catalogChanges($connection, 0, null, 1, $onRetry)['through'];
        } catch (ApiReceptionException $exception) {
            // Une ApiRecept installée avant le flux de révisions sait encore
            // livrer les ressources historiques /records. Le site conserve
            // alors une synchronisation complète au lieu de bloquer le
            // catalogue pendant le déploiement progressif de l'API.
            if ($exception->getCode() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * @return array{through: int, nextCursor: int, total: int, hasMore: bool, items: list<array<string, mixed>>}
     */
    public function catalogChanges(ApiReceptionConnection $connection, int $after, ?int $through, int $limit = self::PAGE_SIZE, ?callable $onRetry = null): array
    {
        $after = max(0, $after);
        $limit = max(1, min($limit, 500));
        $path = sprintf('/api/v1/catalog/changes?after=%d&limit=%d', $after, $limit);
        if ($through !== null) {
            $path .= '&through=' . max(0, $through);
        }
        $payload = $this->getJson($connection, $path, $this->apiKey($connection), $onRetry);

        $responseThrough = $payload['through'] ?? null;
        $nextCursor = $payload['nextCursor'] ?? null;
        $total = $payload['total'] ?? null;
        $hasMore = $payload['hasMore'] ?? null;
        $items = $payload['items'] ?? null;
        if (!is_int($responseThrough) || !is_int($nextCursor) || !is_int($total) || !is_bool($hasMore) || !is_array($items) || !array_is_list($items)) {
            throw new ApiReceptionException('ApiRecept a retourné un flux de modifications du catalogue invalide.');
        }
        if ($responseThrough < $after || ($through !== null && $responseThrough !== $through) || $nextCursor < $after || $nextCursor > $responseThrough || $total < 0) {
            throw new ApiReceptionException('ApiRecept a retourné des curseurs de catalogue incohérents.');
        }

        $previousRevision = $after;
        foreach ($items as $item) {
            if (!is_array($item) || array_is_list($item)) {
                throw new ApiReceptionException('ApiRecept a retourné une modification de catalogue invalide.');
            }
            $revision = $item['revision'] ?? null;
            $resource = $item['resource'] ?? null;
            $sourceId = $item['sourceId'] ?? null;
            $operation = $item['operation'] ?? null;
            if (!is_int($revision) || $revision <= $previousRevision || $revision > $responseThrough
                || !is_string($resource) || !in_array($resource, ['items', 'item_families'], true)
                || !is_string($sourceId) || trim($sourceId) === ''
                || !is_string($operation) || !in_array($operation, ['upsert', 'delete'], true)) {
                throw new ApiReceptionException('ApiRecept a retourné une modification de catalogue incohérente.');
            }
            if ($operation === 'upsert' && (!isset($item['data']) || !is_array($item['data']) || array_is_list($item['data']))) {
                throw new ApiReceptionException('Une modification de catalogue ne contient pas ses données EBP.');
            }
            $previousRevision = $revision;
        }
        if ($items !== [] && $nextCursor !== $previousRevision) {
            throw new ApiReceptionException('ApiRecept a retourné un curseur de catalogue incomplet.');
        }
        if ($items === [] && $nextCursor !== $responseThrough) {
            throw new ApiReceptionException('ApiRecept n’a pas clôturé correctement le flux de modifications.');
        }

        /** @var list<array<string, mixed>> $items */
        return ['through' => $responseThrough, 'nextCursor' => $nextCursor, 'total' => $total, 'hasMore' => $hasMore, 'items' => $items];
    }

    /**
     * @return array<string, string> association ItemFamily.Id => ItemFamily.Caption
     */
    /** @param null|callable(int, int, int): void $onRetry */
    public function itemFamilyNames(ApiReceptionConnection $connection, ?callable $onRetry = null): array
    {
        /**
         * Chaque entrée contient le libellé et indique si elle provient de la
         * clé EBP canonique. D'anciens lots regroupés pouvaient inclure une
         * clé technique préfixée par la base source : ils ne doivent pas
         * empêcher un catalogue récent de se synchroniser.
         *
         * @var array<string, array{caption: string, canonical: bool, sourceId: string}>
         */
        $families = [];
        $expectedTotal = null;
        $receivedRows = 0;

        for ($page = 1; ; ++$page) {
            $remote = $this->recordsPage($connection, 'item_families', $page, 'familles d’articles', $onRetry);
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
                $sourceId = $this->recordText($record['sourceId'] ?? null, 191);
                $this->indexFamilyName($families, $id, $caption, $sourceId);

                // ApiLocal ajoute une identité technique lorsque deux bases
                // EBP utilisent le même ItemFamily.Id. Les articles portent
                // alors cette même identité dans __apilocalFamilySourceId,
                // ce qui permet de retrouver le bon libellé sans exposer la
                // clé technique dans le catalogue public.
                if ($sourceId !== '' && $sourceId !== $id) {
                    $this->indexFamilyName($families, $sourceId, $caption, $sourceId);
                }
            }

            $receivedRows += count($remote['items']);
            if ($receivedRows > $expectedTotal) {
                throw new ApiReceptionException('ApiRecept a retourné plus de familles d’articles que prévu.');
            }
            if ($receivedRows === $expectedTotal) {
                break;
            }
        }

        /** @var array<string, string> $names */
        $names = array_map(static fn (array $family): string => $family['caption'], $families);

        return $names;
    }

    public function requestPhotoRefresh(ApiReceptionConnection $connection, string $uniqueId): array
    {
        return $this->requestJson($connection, '/api/v1/photo-requests', $this->apiKey($connection), 'POST', ['uniqueId' => $uniqueId]);
    }

    /** @return array<string, mixed> */
    public function requestCustomerImport(ApiReceptionConnection $connection, string $uniqueId): array
    {
        return $this->requestJson($connection, '/api/v1/customer-imports', $this->apiKey($connection), 'POST', ['uniqueId' => $uniqueId]);
    }

    /** @return array<string, mixed> */
    public function customerImport(ApiReceptionConnection $connection, int $id): array
    {
        return $this->requestJson($connection, '/api/v1/customer-imports/' . $id, $this->apiKey($connection));
    }

    /** @return array<string, mixed> */
    public function deleteCustomerImport(ApiReceptionConnection $connection, int $id): array
    {
        return $this->requestJson($connection, '/api/v1/customer-imports/' . $id, $this->apiKey($connection), 'DELETE');
    }

    /** @return array<string, mixed> */
    public function clearCustomerImports(ApiReceptionConnection $connection): array
    {
        return $this->requestJson($connection, '/api/v1/customer-imports', $this->apiKey($connection), 'DELETE');
    }

    /** @return array<string, mixed> */
    public function requestInvoiceImport(ApiReceptionConnection $connection, string $uniqueId, ?string $fromDate = null, ?string $toDate = null): array
    {
        return $this->requestJson($connection, '/api/v1/invoice-imports', $this->apiKey($connection), 'POST', [
            'uniqueId' => $uniqueId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    /** @return array<string, mixed> */
    public function invoiceImport(ApiReceptionConnection $connection, int $id): array
    {
        return $this->requestJson($connection, '/api/v1/invoice-imports/' . $id, $this->apiKey($connection));
    }

    /** @param array<string, mixed> $order @return array<string, mixed> */
    public function submitOrderExport(ApiReceptionConnection $connection, array $order): array
    {
        return $this->requestJson($connection, '/api/v1/order-exports', $this->apiKey($connection), 'POST', $order);
    }

    /** @return array<string, mixed> */
    public function orderExport(ApiReceptionConnection $connection, int $id): array
    {
        return $this->requestJson($connection, '/api/v1/order-exports/' . $id, $this->apiKey($connection));
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
    /** @param null|callable(int, int, int): void $onRetry */
    private function recordsPage(ApiReceptionConnection $connection, string $resource, int $page, string $label, ?callable $onRetry = null): array
    {
        $payload = $this->getJson(
            $connection,
            sprintf('/api/v1/records/%s?page=%d&pageSize=%d', $resource, $page, self::PAGE_SIZE),
            $this->apiKey($connection),
            $onRetry,
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

    /**
     * @param array<string, array{caption: string, canonical: bool, sourceId: string}> $families
     */
    private function indexFamilyName(array &$families, string $id, string $caption, string $sourceId): void
    {
        // L'absence de sourceId est seulement tolérée pour les lots produits
        // avant son ajout dans la réponse ApiRecept. Pour rester compatible,
        // cette donnée est alors considérée comme canonique.
        $candidate = [
            'caption' => $caption,
            'canonical' => $sourceId === '' || $sourceId === $id,
            'sourceId' => $sourceId,
        ];
        $this->keepPreferredFamilyName($families, $id, $candidate, $id);

        $numericAlias = ApiCatalogFamilyKey::numericAlias($id);
        if ($numericAlias === null) {
            return;
        }

        // Cet alias ne sert qu'au rapprochement d'anciens Id numériques. En
        // cas de 011 / 11, le premier enregistrement (le plus récent dans la
        // réponse ApiRecept) reste la valeur de repli ; les identifiants exacts
        // restent, eux, tous les deux disponibles ci-dessus.
        $this->keepPreferredFamilyName($families, $numericAlias, $candidate, $id);
    }

    /**
     * @param array<string, array{caption: string, canonical: bool, sourceId: string}> $families
     * @param array{caption: string, canonical: bool, sourceId: string} $candidate
     */
    private function keepPreferredFamilyName(array &$families, string $key, array $candidate, string $familyId): void
    {
        $current = $families[$key] ?? null;
        if ($current === null) {
            $families[$key] = $candidate;

            return;
        }

        if ($current['caption'] === $candidate['caption']) {
            if ($candidate['canonical'] && !$current['canonical']) {
                $families[$key] = $candidate;
            }

            return;
        }

        // Une donnée de la clé canonique EBP prévaut toujours sur une ancienne
        // entrée technique. À priorité égale, l'ordre de l'API (plus récent en
        // premier) rend le choix déterministe et évite de bloquer le catalogue.
        $replace = $candidate['canonical'] && !$current['canonical'];
        $winner = $replace ? $candidate : $current;
        $discarded = $replace ? $current : $candidate;
        if ($replace) {
            $families[$key] = $candidate;
        }

        $this->logger->warning('Conflit de libellé de famille EBP résolu automatiquement.', [
            'familyId' => $familyId,
            'lookupKey' => $key,
            'keptCaption' => $winner['caption'],
            'keptSourceId' => $winner['sourceId'],
            'discardedCaption' => $discarded['caption'],
            'discardedSourceId' => $discarded['sourceId'],
        ]);
    }

    /** @return array<string, mixed> */
    /** @param null|callable(int, int, int): void $onRetry */
    private function getJson(ApiReceptionConnection $connection, string $path, ?string $apiKey = null, ?callable $onRetry = null): array
    {
        return $this->requestJson($connection, $path, $apiKey, onRetry: $onRetry);
    }

    /** @param null|callable(int, int, int): void $onRetry */
    private function requestJson(ApiReceptionConnection $connection, string $path, ?string $apiKey = null, string $method = 'GET', ?array $body = null, float $timeout = 20, ?callable $onRetry = null): array
    {
        $url = $this->url($connection, $path);
        $headers = ['Accept' => 'application/json'];
        if ($apiKey !== null) {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        $retry = 0;
        while (true) {
            try {
                $response = $this->httpClient->request($method, $url, [
                    'headers' => $headers,
                    'timeout' => $timeout,
                    ...($method === 'POST' || $method === 'DELETE' || $timeout < 20 ? ['max_duration' => $timeout] : []),
                    'max_redirects' => 0,
                    ...($body === null ? [] : ['json' => $body]),
                ]);
                $statusCode = $response->getStatusCode();
            } catch (ExceptionInterface $exception) {
                if ($this->canRetry($method, $retry)) {
                    $this->waitBeforeRetry(++$retry, $onRetry);
                    continue;
                }

                $attempts = $retry + 1;
                throw new ApiReceptionException(sprintf('Impossible de joindre ApiRecept après %d tentative(s). Vérifiez l’URL, le certificat HTTPS et le réseau.', $attempts), 0, $exception);
            }

            $accepted = match ($method) {
                'POST' => [200, 201, 202],
                'DELETE' => [200],
                default => [200],
            };
            if (!in_array($statusCode, $accepted, true)) {
                if ($this->canRetry($method, $retry, $statusCode)) {
                    // Libère la réponse avant de réutiliser la connexion HTTP.
                    $response->getContent(false);
                    $this->waitBeforeRetry(++$retry, $onRetry);
                    continue;
                }
                if ($statusCode === 401 || $statusCode === 403) {
                    throw new ApiReceptionException('ApiRecept a refusé la clé API enregistrée.', $statusCode);
                }
                throw new ApiReceptionException(sprintf('ApiRecept a répondu avec le statut HTTP %d.', $statusCode), $statusCode);
            }

            try {
                $payload = $response->toArray(false);
            } catch (\JsonException $exception) {
                throw new ApiReceptionException('ApiRecept a retourné une réponse JSON invalide.', 0, $exception);
            }

            if (!is_array($payload) || array_is_list($payload)) {
                throw new ApiReceptionException('ApiRecept a retourné une réponse JSON invalide.');
            }

            return $payload;
        }
    }

    private function canRetry(string $method, int $retry, ?int $statusCode = null): bool
    {
        if ($method !== 'GET' || $retry >= count(self::TRANSIENT_RETRY_DELAYS_MS)) {
            return false;
        }

        return $statusCode === null || in_array($statusCode, [408, 425, 429, 500, 502, 503, 504], true);
    }

    /** @param null|callable(int, int, int): void $onRetry */
    private function waitBeforeRetry(int $retry, ?callable $onRetry): void
    {
        $delayMs = self::TRANSIENT_RETRY_DELAYS_MS[$retry - 1];
        if ($onRetry !== null) {
            $onRetry($retry, count(self::TRANSIENT_RETRY_DELAYS_MS) + 1, $delayMs);
        }
        usleep($delayMs * 1000);
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
