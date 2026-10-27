<?php

namespace App\Service;

use App\Entity\ApiCatalogSyncRun;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ApiCatalogSyncProgress
{
    private const LOG_LIMIT = 200;

    private array $state = [];
    private float $lastArticlePublication = 0.0;

    public function __construct(
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function start(ApiCatalogSyncRun $run): void
    {
        $this->state = [
            'phase' => 'Lecture des familles d’articles',
            'currentPage' => 0,
            'totalKnown' => false,
            'currentArticle' => null,
            'logs' => [],
            'sequence' => 0,
        ];
        $this->lastArticlePublication = 0.0;
        $this->log('step', 'Connexion à ApiRecept et lecture des familles d’articles.');
        $this->publish($run);
    }

    public function phase(ApiCatalogSyncRun $run, string $phase, ?int $page = null, bool $totalKnown = false): void
    {
        $this->hydrate($run);
        $this->state['phase'] = $phase;
        if ($page !== null) {
            $this->state['currentPage'] = $page;
        }
        $this->state['totalKnown'] = $totalKnown || ($this->state['totalKnown'] ?? false);
        $this->log('step', $phase);
        $this->publish($run);
    }

    /** Records an informational event without changing the current processing phase. */
    public function notice(ApiCatalogSyncRun $run, string $message): void
    {
        $this->hydrate($run);
        $this->log('info', $message);
        $this->publish($run);
    }

    public function retrying(ApiCatalogSyncRun $run, int $retry, int $maxAttempts, int $delayMs): void
    {
        $this->hydrate($run);
        $delay = number_format($delayMs / 1000, $delayMs % 1000 === 0 ? 0 : 1, ',', '');
        $this->log('info', sprintf('Connexion ApiRecept momentanément instable : nouvelle tentative %d/%d dans %s s.', min($maxAttempts, $retry + 1), $maxAttempts, $delay));
        $this->publish($run);
    }

    /** @param array<string, mixed> $row */
    public function article(ApiCatalogSyncRun $run, array $row, int $position): void
    {
        $this->hydrate($run);
        $this->state['currentArticle'] = [
            'reference' => $this->text($row['Id'] ?? '', 64),
            'designation' => $this->text($row['DesComClear'] ?? $row['Caption'] ?? '', 255),
            'uniqueId' => $this->text($row['UniqueId'] ?? '', 36),
            'position' => $position,
        ];

        // Évite une écriture disque par article tout en publiant l'article réellement traité.
        $now = microtime(true);
        if ($now - $this->lastArticlePublication >= 0.25) {
            $this->lastArticlePublication = $now;
            $this->publish($run);
        }
    }

    /** @param list<array{reference: string, designation: string, outcome: string}> $articles */
    public function committed(ApiCatalogSyncRun $run, array $articles): void
    {
        $this->hydrate($run);
        foreach ($articles as $article) {
            $this->log(
                'article',
                sprintf('%s · %s — %s', $article['reference'], $article['designation'], $article['outcome']),
                ['article' => $article],
            );
        }
        $summary = $run->toSummary();
        $this->log('step', sprintf('%d / %d articles enregistrés · %d restants.', $summary['receivedRows'], $summary['remoteTotal'], max(0, $summary['remoteTotal'] - $summary['receivedRows'])));
        $this->publish($run);
    }

    public function finish(ApiCatalogSyncRun $run): void
    {
        $this->hydrate($run);
        $summary = $run->toSummary();
        $failed = $run->getStatus() === ApiCatalogSyncRun::STATUS_ERROR;
        $this->state['phase'] = $failed ? 'Synchronisation interrompue' : 'Synchronisation terminée';
        $this->log($failed ? 'error' : 'step', (string) $summary['message']);
        $this->publish($run);
    }

    /** @return array<string, mixed> */
    public function read(ApiCatalogSyncRun $run): array
    {
        $item = $this->cache->getItem($this->key($run));

        return $item->isHit() && is_array($item->get()) ? $item->get() : [];
    }

    /** @param array<string, mixed> $details */
    private function log(string $type, string $message, array $details = []): void
    {
        $this->state['logs'][] = [
            'id' => ++$this->state['sequence'],
            'at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'type' => $type,
            'message' => $message,
        ] + $details;
        $this->state['logs'] = array_slice($this->state['logs'], -self::LOG_LIMIT);
    }

    private function publish(ApiCatalogSyncRun $run): void
    {
        $this->state['updatedAt'] = (new \DateTimeImmutable())->format(DATE_ATOM);
        $item = $this->cache->getItem($this->key($run));
        $item->set($this->state)->expiresAfter(86400);
        $this->cache->save($item);
    }

    private function key(ApiCatalogSyncRun $run): string
    {
        return 'api_catalog_sync_progress.' . $run->getId();
    }

    private function hydrate(ApiCatalogSyncRun $run): void
    {
        if ($this->state !== []) {
            return;
        }

        $this->state = $this->read($run);
        $this->state['logs'] ??= [];
        $this->state['sequence'] ??= 0;
        $this->state['currentArticle'] ??= null;
        $this->state['totalKnown'] ??= false;
    }

    private function text(mixed $value, int $limit): string
    {
        return is_scalar($value) ? mb_substr(trim((string) $value), 0, $limit, 'UTF-8') : '';
    }
}
