<?php

namespace App\Service;

use App\Entity\ApiCatalogSyncRun;
use App\Entity\Article;
use App\Exception\ApiReceptionException;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;

final class ApiCatalogSynchronizer
{
    public function __construct(
        private readonly ApiReceptionConnectionProvider $connections,
        private readonly ApiReceptionClient $client,
        private readonly ApiCatalogRecordMapper $mapper,
        private readonly ArticleRepository $articles,
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
        if ($lastSyncedAt === null) {
            return true;
        }

        return $lastSyncedAt <= new \DateTimeImmutable(sprintf('-%d minutes', $connection->getSyncIntervalMinutes()));
    }

    public function synchronize(): ApiCatalogSyncRun
    {
        $lock = $this->locks->createLock('api-reception-catalog-sync', 900.0);
        if (!$lock->acquire()) {
            throw new ApiReceptionException('Une synchronisation du catalogue est déjà en cours.');
        }

        try {
            $connection = $this->connections->get();
            if (!$connection->isEnabled()) {
                throw new ApiReceptionException('Activez la connexion ApiRecept avant de synchroniser le catalogue.');
            }

            $run = new ApiCatalogSyncRun($connection);
            $this->entityManager->persist($run);
            $this->entityManager->flush();

            try {
                $this->progress->start($run);
                $familyNamesById = $this->client->itemFamilyNames($connection);
                $seenUniqueIds = [];
                $expectedTotal = null;
                $receivedRows = 0;

                for ($page = 1; ; ++$page) {
                    $lock->refresh();
                    $this->progress->phase($run, sprintf('Réception de la page %d depuis ApiRecept', $page), $page);
                    $remote = $this->client->itemsPage($connection, $page);
                    if ($expectedTotal === null) {
                        $expectedTotal = $remote['total'];
                        $run->setRemoteTotal($expectedTotal);
                        $this->entityManager->flush();
                    } elseif ($expectedTotal !== $remote['total']) {
                        throw new ApiReceptionException('Le catalogue ApiRecept a changé pendant la synchronisation. Réessayez dans quelques instants.');
                    }
                    $this->progress->phase($run, sprintf('Vérification des articles de la page %d', $page), $page, totalKnown: true);

                    $items = $remote['items'];
                    if ($items === []) {
                        if ($receivedRows !== $expectedTotal) {
                            throw new ApiReceptionException('ApiRecept a retourné une pagination incomplète. Aucune suppression locale n’a été effectuée.');
                        }
                        break;
                    }

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

                    $this->applyPage($normalized, $run);
                    $receivedRows += count($normalized);

                    if ($receivedRows === $expectedTotal) {
                        break;
                    }
                }

                $this->progress->phase($run, 'Finalisation du catalogue local');
                $removedRows = $seenUniqueIds === [] ? 0 : $this->removeMissingArticles($seenUniqueIds);
                $run->setRemovedRows($removedRows);
                $run->complete(sprintf(
                    'Synchronisation terminée : %d article(s) reçus, %d créé(s), %d mis à jour, %d inchangé(s), %d retiré(s).',
                    $run->toSummary()['receivedRows'],
                    $run->toSummary()['createdRows'],
                    $run->toSummary()['updatedRows'],
                    $run->toSummary()['unchangedRows'],
                    $removedRows,
                ));
                $connection->markSynced();
                $this->entityManager->flush();
                $this->progress->finish($run);

                return $run;
            } catch (\Throwable $exception) {
                $message = $exception instanceof ApiReceptionException
                    ? $exception->getMessage()
                    : 'La synchronisation du catalogue a échoué. Consultez les journaux du serveur.';
                $run->fail($message);
                $connection->markError($message);
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

    /** @param list<array<string, mixed>> $rows */
    private function applyPage(array $rows, ApiCatalogSyncRun $run): void
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
        $this->entityManager->flush();
        $this->progress->committed($run, $committedArticles);

        // Doctrine ORM 3 n'accepte plus une classe dans clear(). L'appel historique
        // clear(Article::class) détachait donc aussi $run : seul le premier lot était
        // visible dans le tableau de bord. On libère uniquement les articles de ce lot.
        foreach ($managedArticles as $article) {
            $this->entityManager->detach($article);
        }
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
}
