<?php

namespace App\Service;

use App\Entity\ApiCatalogSyncRun;
use App\Entity\Article;
use App\Exception\ApiReceptionException;
use App\Repository\ApiCatalogSyncRunRepository;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;

final class ApiCatalogSynchronizer
{
    private const MODE_FULL = 'full';
    private const MODE_INCREMENTAL = 'incremental';

    public function __construct(
        private readonly ApiReceptionConnectionProvider $connections,
        private readonly ApiReceptionClient $client,
        private readonly ApiCatalogRecordMapper $mapper,
        private readonly ArticleRepository $articles,
        private readonly ApiCatalogSyncRunRepository $runs,
        private readonly EntityManagerInterface $entityManager,
        private readonly LockFactory $locks,
        private readonly ApiCatalogSyncProgress $progress,
    ) {
    }

    public function isDue(): bool
    {
        $connection = $this->connections->get();
        if (!$connection->isEnabled() || !$connection->hasApiKey() || $connection->getBaseUrl() === null) {
            return false;
        }

        $lastSyncedAt = $connection->getLastSyncedAt();

        return $lastSyncedAt === null
            || $lastSyncedAt <= new \DateTimeImmutable(sprintf('-%d minutes', $connection->getSyncIntervalMinutes()));
    }

    public function synchronize(): ApiCatalogSyncRun
    {
        $run = $this->start();
        while ($run->getStatus() === ApiCatalogSyncRun::STATUS_RUNNING) {
            $run = $this->advance((int) $run->getId());
        }

        return $run;
    }

    /** Starts a resumable run without making a long remote request. */
    public function start(): ApiCatalogSyncRun
    {
        return $this->initiate(false);
    }

    /** Returns the interactive run already in progress when it can be resumed. */
    public function startOrResume(): ApiCatalogSyncRun
    {
        return $this->initiate(true);
    }

    private function initiate(bool $resume): ApiCatalogSyncRun
    {
        $lock = $this->locks->createLock('api-reception-catalog-sync-start', 30.0);
        if (!$lock->acquire()) {
            throw new ApiReceptionException('Une synchronisation du catalogue est déjà en cours.');
        }

        try {
            $running = $this->runs->findRunning();
            if ($running !== null) {
                if ($resume && $this->isResumable($running)) {
                    return $running;
                }
                if (!$resume) {
                    throw new ApiReceptionException('Une synchronisation du catalogue est déjà en cours.');
                }

                $running->fail('Une ancienne synchronisation incomplète a été arrêtée avant le démarrage d’un nouveau suivi.');
                $this->entityManager->flush();
                $this->progress->finish($running);
            }

            $connection = $this->connections->get();
            if (!$connection->isEnabled()) {
                throw new ApiReceptionException('Activez la connexion ApiRecept avant de synchroniser le catalogue.');
            }

            $cursor = $connection->getCatalogRevision();
            $mode = $cursor > 0 ? self::MODE_INCREMENTAL : self::MODE_FULL;
            $run = new ApiCatalogSyncRun($connection);
            $run->setProgressData([
                'mode' => $mode,
                // The first full import captures its revision before reading
                // records. Any later change remains available to delta sync.
                'phase' => $mode === self::MODE_FULL ? 'capture_revision' : 'families',
                'nextPage' => 1,
                'expectedTotal' => null,
                'familyNames' => [],
                'seenUniqueIds' => [],
                'baseRevision' => null,
                'changeFeedAvailable' => null,
                'afterRevision' => $cursor,
                'throughRevision' => null,
                'changedFamilyIds' => [],
            ]);
            $this->entityManager->persist($run);
            $this->entityManager->flush();
            $this->progress->start($run);

            return $run;
        } finally {
            $lock->release();
        }
    }

    private function isResumable(ApiCatalogSyncRun $run): bool
    {
        $phase = $run->getProgressData()['phase'] ?? null;

        return is_string($phase) && in_array($phase, [
            'capture_revision', 'families', 'items', 'finalize', 'changes', 'finalize_changes',
        ], true);
    }

    /** Processes one small remote stage, so the AJAX page stays responsive. */
    public function advance(int $runId): ApiCatalogSyncRun
    {
        $lock = $this->locks->createLock('api-reception-catalog-sync', 120.0);
        if (!$lock->acquire()) {
            throw new ApiReceptionException('Le lot précédent est encore en cours de traitement.');
        }

        try {
            $run = $this->runs->find($runId);
            if (!$run instanceof ApiCatalogSyncRun) {
                throw new ApiReceptionException('La synchronisation demandée est introuvable.');
            }
            if ($run->getStatus() !== ApiCatalogSyncRun::STATUS_RUNNING) {
                return $run;
            }

            try {
                $state = $run->getProgressData();
                $phase = is_string($state['phase'] ?? null) ? $state['phase'] : '';
                $connection = $run->getConnection();

                if ($phase === 'capture_revision') {
                    $this->progress->phase($run, 'Préparation de la synchronisation complète');
                    $catalogHead = $this->client->catalogHead($connection, $this->retryReporter($run));
                    $state['baseRevision'] = $catalogHead ?? 0;
                    $state['changeFeedAvailable'] = $catalogHead !== null;
                    $state['phase'] = 'families';
                    $run->setProgressData($state);
                    $this->entityManager->flush();

                    if ($catalogHead === null) {
                        $this->progress->notice($run, 'ApiRecept utilise encore le mode catalogue historique : synchronisation complète sécurisée en cours. Les mises à jour partielles seront activées automatiquement après la mise à jour d’ApiRecept.');
                    }

                    return $run;
                }

                if ($phase === 'families') {
                    $this->progress->phase($run, 'Lecture des familles d’articles depuis ApiRecept');
                    $state['familyNames'] = $this->client->itemFamilyNames($connection, $this->retryReporter($run));
                    $state['phase'] = ($state['mode'] ?? self::MODE_FULL) === self::MODE_INCREMENTAL ? 'changes' : 'items';
                    $run->setProgressData($state);
                    $this->entityManager->flush();

                    return $run;
                }

                if ($phase === 'items') {
                    return $this->advanceItems($run, $state);
                }
                if ($phase === 'finalize') {
                    return $this->finalizeFull($run, $state);
                }
                if ($phase === 'changes') {
                    return $this->advanceChanges($run, $state);
                }
                if ($phase === 'finalize_changes') {
                    return $this->finalizeChanges($run, $state);
                }

                throw new ApiReceptionException('L’état de synchronisation du catalogue est invalide. Relancez la mise à jour.');
            } catch (\Throwable $exception) {
                $message = $exception instanceof ApiReceptionException
                    ? $exception->getMessage()
                    : 'La synchronisation du catalogue a échoué. Consultez les journaux du serveur.';
                $run->fail($message);
                $run->getConnection()->markError($message);
                $this->entityManager->flush();
                $this->progress->finish($run);

                if ($exception instanceof ApiReceptionException) {
                    throw $exception;
                }

                throw new ApiReceptionException($message, 0, $exception);
            }
        } finally {
            $lock->release();
        }
    }

    /** @param array<string, mixed> $state */
    private function advanceItems(ApiCatalogSyncRun $run, array $state): ApiCatalogSyncRun
    {
        $page = max(1, (int) ($state['nextPage'] ?? 1));
        $this->progress->phase($run, sprintf('Réception de la page %d depuis ApiRecept', $page), $page);
        $remote = $this->client->itemsPage($run->getConnection(), $page, $this->retryReporter($run));
        $expectedTotal = isset($state['expectedTotal']) && is_int($state['expectedTotal']) ? $state['expectedTotal'] : null;
        if ($expectedTotal === null) {
            $expectedTotal = $remote['total'];
            $state['expectedTotal'] = $expectedTotal;
            $run->setRemoteTotal($expectedTotal);
        } elseif ($expectedTotal !== $remote['total']) {
            throw new ApiReceptionException('Le catalogue ApiRecept a changé pendant la synchronisation. Réessayez dans quelques instants.');
        }
        $this->progress->phase($run, sprintf('Vérification des articles de la page %d', $page), $page, totalKnown: true);

        $items = $remote['items'];
        $receivedRows = (int) $run->toSummary()['receivedRows'];
        if ($items === []) {
            if ($receivedRows !== $expectedTotal) {
                throw new ApiReceptionException('ApiRecept a retourné une pagination incomplète. Aucune suppression locale n’a été effectuée.');
            }
            $state['phase'] = 'finalize';
            $run->setProgressData($state);
            $this->progress->phase($run, 'Finalisation du catalogue local', $page, totalKnown: true);
            $this->entityManager->flush();

            return $run;
        }

        $familyNamesById = is_array($state['familyNames'] ?? null) ? $state['familyNames'] : [];
        $seenUniqueIds = is_array($state['seenUniqueIds'] ?? null) ? $state['seenUniqueIds'] : [];
        $normalized = [];
        foreach ($items as $item) {
            $this->progress->article($run, is_array($item['data'] ?? null) ? $item['data'] : [], $receivedRows + count($normalized) + 1);
            $row = $this->mapper->normalize($item, $familyNamesById);
            $uniqueId = $row['UniqueId'];
            if (isset($seenUniqueIds[$uniqueId])) {
                throw new ApiReceptionException('ApiRecept a retourné deux fois le même article. Aucune suppression locale n’a été effectuée.');
            }
            $seenUniqueIds[$uniqueId] = true;
            $normalized[] = $row;
        }
        if ($receivedRows + count($normalized) > $expectedTotal) {
            throw new ApiReceptionException('ApiRecept a retourné plus d’articles que prévu. Aucune suppression locale n’a été effectuée.');
        }

        [$managedArticles, $committedArticles] = $this->applyPage($normalized, $run);
        $state['seenUniqueIds'] = $seenUniqueIds;
        $state['nextPage'] = $page + 1;
        if ((int) $run->toSummary()['receivedRows'] === $expectedTotal) {
            $state['phase'] = 'finalize';
        }
        $run->setProgressData($state);
        $this->entityManager->flush();
        $this->progress->committed($run, $committedArticles);
        if ($state['phase'] === 'finalize') {
            $this->progress->phase($run, 'Finalisation du catalogue local', $page, totalKnown: true);
        }
        $this->detachAll($managedArticles);

        return $run;
    }

    /** @param array<string, mixed> $state */
    private function finalizeFull(ApiCatalogSyncRun $run, array $state): ApiCatalogSyncRun
    {
        $this->progress->phase($run, 'Finalisation du catalogue local', totalKnown: true);
        $seenUniqueIds = is_array($state['seenUniqueIds'] ?? null) ? $state['seenUniqueIds'] : [];
        $removedRows = $seenUniqueIds === [] ? 0 : $this->removeMissingArticles($seenUniqueIds);
        $run->setRemovedRows($removedRows);
        $baseRevision = max(0, (int) ($state['baseRevision'] ?? 0));
        $changeFeedAvailable = ($state['changeFeedAvailable'] ?? false) === true;
        $summary = $run->toSummary();
        $run->complete(sprintf(
            'Synchronisation complète terminée : %d article(s) reçus, %d créé(s), %d mis à jour, %d inchangé(s), %d retiré(s).',
            $summary['receivedRows'], $summary['createdRows'], $summary['updatedRows'], $summary['unchangedRows'], $removedRows,
        ));
        // Sur une ApiRecept historique, le curseur reste volontairement à 0 :
        // la prochaine exécution restera complète. Dès que l'endpoint de
        // révisions est disponible, la première synchronisation le capture.
        $run->getConnection()->setCatalogRevision($changeFeedAvailable ? $baseRevision : 0)->markSynced();
        $run->clearProgressData();
        $this->entityManager->flush();
        $this->progress->finish($run);

        return $run;
    }

    /** @param array<string, mixed> $state */
    private function advanceChanges(ApiCatalogSyncRun $run, array $state): ApiCatalogSyncRun
    {
        $after = max(0, (int) ($state['afterRevision'] ?? 0));
        $through = isset($state['throughRevision']) && is_int($state['throughRevision']) ? $state['throughRevision'] : null;
        $this->progress->phase($run, 'Lecture des modifications du catalogue depuis ApiRecept', totalKnown: $through !== null);
        $remote = $this->client->catalogChanges($run->getConnection(), $after, $through, onRetry: $this->retryReporter($run));
        if ($through === null) {
            $through = $remote['through'];
            $state['throughRevision'] = $through;
            $run->setRemoteTotal($remote['total']);
        }
        if ($remote['through'] !== $through) {
            throw new ApiReceptionException('ApiRecept a modifié la fenêtre de synchronisation. Relancez la mise à jour.');
        }

        $changes = $remote['items'];
        if ($changes === []) {
            $state['afterRevision'] = $remote['nextCursor'];
            $state['phase'] = 'finalize_changes';
            $run->setProgressData($state);
            $this->entityManager->flush();

            return $run;
        }

        $familyNames = is_array($state['familyNames'] ?? null) ? $state['familyNames'] : [];
        [$itemChanges, $familyIds] = $this->prepareItemChanges($changes, $familyNames, $run);
        [$managedArticles, $committedArticles] = $this->applyIncrementalItemChanges($itemChanges, $run);
        $run->addReceivedRows(count($changes));
        $state['afterRevision'] = $remote['nextCursor'];
        $oldFamilyIds = is_array($state['changedFamilyIds'] ?? null) ? $state['changedFamilyIds'] : [];
        $state['changedFamilyIds'] = array_values(array_unique([...$oldFamilyIds, ...$familyIds]));
        $run->setProgressData($state);
        $this->entityManager->flush();
        $this->progress->committed($run, $committedArticles);
        $this->detachAll($managedArticles);

        return $run;
    }

    /** @param array<string, mixed> $state */
    private function finalizeChanges(ApiCatalogSyncRun $run, array $state): ApiCatalogSyncRun
    {
        $this->progress->phase($run, 'Finalisation des modifications locales', totalKnown: true);
        $familyNames = is_array($state['familyNames'] ?? null) ? $state['familyNames'] : [];
        $familyIds = is_array($state['changedFamilyIds'] ?? null) ? $state['changedFamilyIds'] : [];
        foreach ($familyIds as $familyId) {
            if (!is_string($familyId) || $familyId === '') {
                continue;
            }
            $caption = $this->familyCaption($familyNames, $familyId);
            if ($caption === null) {
                throw new ApiReceptionException(sprintf('La famille « %s » a disparu d’ApiRecept alors que des articles peuvent encore y être rattachés.', $familyId));
            }
            $this->articles->updateFamilyName($familyId, $caption);
        }

        $through = max(0, (int) ($state['throughRevision'] ?? $state['afterRevision'] ?? 0));
        $summary = $run->toSummary();
        $run->complete(sprintf(
            'Synchronisation incrémentale terminée : %d modification(s) reçue(s), %d créé(s), %d mis à jour, %d inchangé(s), %d retiré(s).',
            $summary['receivedRows'], $summary['createdRows'], $summary['updatedRows'], $summary['unchangedRows'], $summary['removedRows'],
        ));
        $run->getConnection()->setCatalogRevision($through)->markSynced();
        $run->clearProgressData();
        $this->entityManager->flush();
        $this->progress->finish($run);

        return $run;
    }

    /**
     * @param list<array<string, mixed>> $changes
     * @param array<string, string> $familyNames
     * @return array{0: list<array{operation: string, sourceId: string, row?: array<string, mixed>}>, 1: list<string>}
     */
    private function prepareItemChanges(array $changes, array $familyNames, ApiCatalogSyncRun $run): array
    {
        $items = [];
        $familyIds = [];
        $position = (int) $run->toSummary()['receivedRows'];
        foreach ($changes as $change) {
            ++$position;
            $resource = $change['resource'];
            $operation = $change['operation'];
            $sourceId = trim((string) $change['sourceId']);
            if ($resource === 'item_families') {
                if ($operation === 'upsert') {
                    $data = $change['data'];
                    $id = trim((string) ($data['Id'] ?? $sourceId));
                    $caption = trim((string) ($data['Caption'] ?? ''));
                    if ($id === '' || $caption === '') {
                        throw new ApiReceptionException('Une famille modifiée ne contient pas les champs « Id » et « Caption ».');
                    }
                    // Une famille provenant d’un export multi-sociétés est
                    // identifiée par son sourceId technique. Ainsi, la mise à
                    // jour du libellé ne touche pas les articles d’une autre
                    // société ayant le même ItemFamily.Id.
                    $familyIds[] = $sourceId !== '' && $sourceId !== $id ? $sourceId : $id;
                }
                continue;
            }
            if ($operation === 'delete') {
                if (!$this->isUniqueId($sourceId)) {
                    throw new ApiReceptionException('ApiRecept a retourné la suppression d’un article avec un UniqueId invalide.');
                }
                $items[] = ['operation' => 'delete', 'sourceId' => $sourceId];
                $this->progress->article($run, ['Id' => $sourceId, 'Caption' => 'Article retiré'], $position);
                continue;
            }
            $row = $this->mapper->normalize(['data' => $change['data']], $familyNames);
            $items[] = ['operation' => 'upsert', 'sourceId' => $sourceId, 'row' => $row];
            $this->progress->article($run, $row, $position);
        }

        return [$items, array_values(array_unique($familyIds))];
    }

    /**
     * @param list<array{operation: string, sourceId: string, row?: array<string, mixed>}> $changes
     * @return array{0: list<Article>, 1: list<array{reference: string, designation: string, outcome: string}>}
     */
    private function applyIncrementalItemChanges(array $changes, ApiCatalogSyncRun $run): array
    {
        $uniqueIds = [];
        foreach ($changes as $change) {
            $uniqueIds[] = $change['operation'] === 'upsert' ? $change['row']['UniqueId'] : $change['sourceId'];
        }
        $existing = [];
        foreach ($this->articles->findByUniqueIds(array_values(array_unique($uniqueIds))) as $article) {
            if ($article->getUniqueId() !== null) {
                $existing[$article->getUniqueId()] = $article;
            }
        }

        $managed = [];
        $committed = [];
        foreach ($changes as $change) {
            $uniqueId = $change['operation'] === 'upsert' ? $change['row']['UniqueId'] : $change['sourceId'];
            if ($change['operation'] === 'delete') {
                $article = $existing[$uniqueId] ?? null;
                if (!$article instanceof Article) {
                    $run->addUnchangedRows(1);
                    $committed[] = ['reference' => $uniqueId, 'designation' => 'Article retiré', 'outcome' => 'Déjà absent'];
                    continue;
                }
                $this->entityManager->remove($article);
                unset($existing[$uniqueId]);
                $run->addRemovedRows(1);
                $committed[] = ['reference' => $article->getId() ?? $uniqueId, 'designation' => $article->getDesComClear() ?? 'Article retiré', 'outcome' => 'Retiré'];
                continue;
            }

            $row = $change['row'];
            $article = $existing[$uniqueId] ?? null;
            if (!$article instanceof Article) {
                $article = (new Article())->setUniqueId($uniqueId);
                $this->mapper->apply($article, $row);
                $this->entityManager->persist($article);
                $existing[$uniqueId] = $article;
                $managed[] = $article;
                $run->addCreatedRows(1);
                $committed[] = ['reference' => $row['Id'], 'designation' => $row['DesComClear'], 'outcome' => 'Créé'];
                continue;
            }

            $managed[] = $article;
            if ($this->mapper->matches($article, $row)) {
                $run->addUnchangedRows(1);
                $committed[] = ['reference' => $row['Id'], 'designation' => $row['DesComClear'], 'outcome' => 'Déjà à jour'];
                continue;
            }
            $this->mapper->apply($article, $row);
            $run->addUpdatedRows(1);
            $committed[] = ['reference' => $row['Id'], 'designation' => $row['DesComClear'], 'outcome' => 'Mis à jour'];
        }

        return [$managed, $committed];
    }

    /** @param list<array<string, mixed>> $rows */
    private function applyPage(array $rows, ApiCatalogSyncRun $run): array
    {
        $this->progress->phase($run, 'Enregistrement des articles dans le catalogue local', totalKnown: true);
        $uniqueIds = array_map(static fn (array $row): string => $row['UniqueId'], $rows);
        $existingByUniqueId = [];
        $managedArticles = [];
        $committedArticles = [];
        $receivedBeforePage = $run->toSummary()['receivedRows'];
        foreach ($this->articles->findByUniqueIds($uniqueIds) as $article) {
            if ($article->getUniqueId() !== null) {
                $existingByUniqueId[$article->getUniqueId()] = $article;
            }
        }

        foreach ($rows as $index => $row) {
            $this->progress->article($run, $row, $receivedBeforePage + $index + 1);
            $article = $existingByUniqueId[$row['UniqueId']] ?? null;
            if (!$article instanceof Article) {
                $article = (new Article())->setUniqueId($row['UniqueId']);
                $this->mapper->apply($article, $row);
                $this->entityManager->persist($article);
                $managedArticles[] = $article;
                $run->addCreatedRows(1);
                $committedArticles[] = ['reference' => $row['Id'], 'designation' => $row['DesComClear'], 'outcome' => 'Créé'];
                continue;
            }
            $managedArticles[] = $article;
            if ($this->mapper->matches($article, $row)) {
                $run->addUnchangedRows(1);
                $committedArticles[] = ['reference' => $row['Id'], 'designation' => $row['DesComClear'], 'outcome' => 'Déjà à jour'];
                continue;
            }
            $this->mapper->apply($article, $row);
            $run->addUpdatedRows(1);
            $committedArticles[] = ['reference' => $row['Id'], 'designation' => $row['DesComClear'], 'outcome' => 'Mis à jour'];
        }
        $run->addReceivedRows(count($rows));

        return [$managedArticles, $committedArticles];
    }

    /** @param array<string, string> $familyNames */
    private function familyCaption(array $familyNames, string $familyId): ?string
    {
        if (isset($familyNames[$familyId])) {
            return $familyNames[$familyId];
        }
        $alias = ApiCatalogFamilyKey::numericAlias($familyId);

        return $alias === null ? null : ($familyNames[$alias] ?? null);
    }

    /** @return \Closure(int, int, int): void */
    private function retryReporter(ApiCatalogSyncRun $run): \Closure
    {
        return function (int $retry, int $maxAttempts, int $delayMs) use ($run): void {
            $this->progress->retrying($run, $retry, $maxAttempts, $delayMs);
        };
    }

    private function isUniqueId(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    /** @param array<string, true> $seenUniqueIds */
    private function removeMissingArticles(array $seenUniqueIds): int
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('article.uniqueId AS uniqueId')
            ->from(Article::class, 'article')
            ->getQuery()
            ->getArrayResult();
        $removedRows = 0;
        foreach ($rows as $row) {
            $uniqueId = (string) ($row['uniqueId'] ?? '');
            if ($uniqueId === '' || isset($seenUniqueIds[$uniqueId])) {
                continue;
            }
            $this->entityManager->remove($this->entityManager->getReference(Article::class, $uniqueId));
            ++$removedRows;
            if (($removedRows % 250) === 0) {
                $this->entityManager->flush();
            }
        }
        if ($removedRows > 0) {
            $this->entityManager->flush();
        }

        return $removedRows;
    }

    /** @param list<Article> $articles */
    private function detachAll(array $articles): void
    {
        foreach ($articles as $article) {
            $this->entityManager->detach($article);
        }
    }
}
