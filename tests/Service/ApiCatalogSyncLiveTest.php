<?php

namespace App\Tests\Service;

use App\Controller\Admin\ApiCatalogSyncController;
use App\Entity\ApiCatalogSyncRun;
use App\Entity\ApiReceptionConnection;
use App\Entity\Article;
use App\Exception\ApiReceptionException;
use App\Repository\ApiReceptionConnectionRepository;
use App\Repository\ApiCatalogSyncRunRepository;
use App\Repository\ArticleRepository;
use App\Service\ApiCatalogRecordMapper;
use App\Service\ApiCatalogSynchronizer;
use App\Service\ApiCatalogSyncProgress;
use App\Service\ApiReceptionClient;
use App\Service\ApiReceptionConnectionProvider;
use App\Service\ApiReceptionCredentialCipher;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

final class ApiCatalogSyncLiveTest extends TestCase
{
    private EntityManager $entityManager;
    private ManagerRegistry $registry;
    private ApiCatalogSyncProgress $progress;
    private ApiReceptionCredentialCipher $cipher;

    protected function setUp(): void
    {
        $configuration = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2) . '/src/Entity'], true);
        $this->entityManager = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]), $configuration);
        (new SchemaTool($this->entityManager))->createSchema(array_map(
            $this->entityManager->getClassMetadata(...),
            [ApiReceptionConnection::class, ApiCatalogSyncRun::class, Article::class],
        ));
        $this->registry = $this->createStub(ManagerRegistry::class);
        $this->registry->method('getManagerForClass')->willReturn($this->entityManager);
        $this->progress = new ApiCatalogSyncProgress(new ArrayAdapter());
        $this->cipher = new ApiReceptionCredentialCipher('sync-live-tests');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setEnabled(true)
            ->setApiKeyCiphertext($this->cipher->encrypt('apr_live_test'));
        $this->entityManager->persist($connection);
        $this->entityManager->flush();
    }

    public function testLiveJournalReportsCommittedArticlesAcrossPagesAndKeepsLocalReductions(): void
    {
        $rows = array_map($this->record(...), range(1, 101));
        $mapper = new ApiCatalogRecordMapper();
        $existing = (new Article())->setUniqueId($rows[0]['data']['UniqueId'])->setReduction('35');
        $mapper->apply($existing, $mapper->normalize($rows[0], []));
        $this->entityManager->persist($existing);
        $this->entityManager->flush();

        $http = new MockHttpClient(function (string $method, string $url) use ($rows): MockResponse {
            if (str_contains($url, '/item_families?')) {
                return $this->response('item_families', 0, []);
            }
            if (str_contains($url, 'page=1&')) {
                return $this->response('items', 101, array_slice($rows, 0, 100));
            }
            $run = $this->entityManager->find(ApiCatalogSyncRun::class, 1);
            self::assertSame(100, $run->toSummary()['receivedRows']);
            self::assertSame(100, (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM article'));
            $live = $this->progress->read($run);
            self::assertSame(2, $live['currentPage']);
            self::assertSame('REF-100', $live['currentArticle']['reference']);
            self::assertStringContainsString('100 / 101 articles enregistrés', implode('\n', array_column($live['logs'], 'message')));

            return $this->response('items', 101, array_slice($rows, 100));
        });

        $run = $this->synchronizer($http)->synchronize();

        self::assertSame(ApiCatalogSyncRun::STATUS_DONE, $run->getStatus());
        self::assertSame(101, $run->toSummary()['receivedRows']);
        self::assertSame(100, $run->toSummary()['createdRows']);
        self::assertSame(1, $run->toSummary()['unchangedRows']);
        self::assertSame('35', $this->entityManager->find(Article::class, $rows[0]['data']['UniqueId'])->getReduction());
        $live = $this->progress->read($run);
        self::assertSame('REF-101', $live['currentArticle']['reference']);
        self::assertSame(101, $live['currentArticle']['position']);
        self::assertSame('Synchronisation terminée', $live['phase']);
        self::assertCount(101, array_filter($live['logs'], static fn (array $entry): bool => $entry['type'] === 'article'));
        self::assertStringContainsString('REF-1 · Pièce 1 — Déjà à jour', implode('\n', array_column($live['logs'], 'message')));
    }

    public function testInvalidArticleIsVisibleAndJournalDoesNotConfirmUncommittedRows(): void
    {
        $invalid = $this->record(2);
        $invalid['data']['UniqueId'] = 'invalid';
        $http = new MockHttpClient([
            $this->response('item_families', 0, []),
            $this->response('items', 2, [$this->record(1), $invalid]),
        ]);

        try {
            $this->synchronizer($http)->synchronize();
            self::fail('The invalid article must interrupt this page.');
        } catch (ApiReceptionException $exception) {
            self::assertStringContainsString('UniqueId', $exception->getMessage());
        }

        $run = $this->entityManager->find(ApiCatalogSyncRun::class, 1);
        self::assertSame(ApiCatalogSyncRun::STATUS_ERROR, $run->getStatus());
        self::assertSame(0, $run->toSummary()['receivedRows']);
        $live = $this->progress->read($run);
        self::assertSame('REF-2', $live['currentArticle']['reference']);
        self::assertSame('error', $live['logs'][array_key_last($live['logs'])]['type']);
        self::assertSame([], array_values(array_filter($live['logs'], static fn (array $entry): bool => $entry['type'] === 'article')));
    }

    public function testJournalIsBoundedAndDoesNotLeakBetweenRuns(): void
    {
        $connection = $this->entityManager->find(ApiReceptionConnection::class, 1);
        $first = new ApiCatalogSyncRun($connection);
        $second = new ApiCatalogSyncRun($connection);
        $this->entityManager->persist($first);
        $this->entityManager->persist($second);
        $this->entityManager->flush();
        $this->progress->start($first);
        $articles = array_fill(0, 300, ['reference' => 'REF', 'designation' => 'Pièce', 'outcome' => 'Créé']);
        $this->progress->committed($first, $articles);
        self::assertCount(200, $this->progress->read($first)['logs']);
        $this->progress->start($second);
        self::assertCount(1, $this->progress->read($second)['logs']);
        self::assertCount(200, $this->progress->read($first)['logs']);
        self::assertFalse($this->progress->read($second)['totalKnown']);
    }

    public function testStatusExposesCurrentArticleAndKeepsALongActiveRunRunning(): void
    {
        $run = new ApiCatalogSyncRun($this->entityManager->find(ApiReceptionConnection::class, 1));
        $run->setRemoteTotal(1000)->addReceivedRows(350);
        (new \ReflectionProperty($run, 'startedAt'))->setValue($run, new \DateTimeImmutable('-20 minutes'));
        $this->entityManager->persist($run);
        $this->entityManager->flush();
        $this->progress->start($run);
        $this->progress->article($run, ['Id' => 'REF-351', 'Caption' => 'Pièce courante'], 351);
        $controller = new ApiCatalogSyncController();
        $controller->setContainer(new Container());

        $response = $controller->status(new ApiCatalogSyncRunRepository($this->registry), new ArticleRepository($this->registry), $this->entityManager, $this->progress);
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('RUNNING', $data['run']['status']);
        self::assertSame(35, $data['progress']['percent']);
        self::assertSame(650, $data['progress']['remainingRows']);
        self::assertSame('REF-351', $data['progress']['currentArticle']['reference']);
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    public function testInterruptedRunAddsAnErrorToTheJournalEvenWithoutCachedProgress(): void
    {
        $run = new ApiCatalogSyncRun($this->entityManager->find(ApiReceptionConnection::class, 1));
        (new \ReflectionProperty($run, 'startedAt'))->setValue($run, new \DateTimeImmutable('-20 minutes'));
        $this->entityManager->persist($run);
        $this->entityManager->flush();
        $controller = new ApiCatalogSyncController();
        $controller->setContainer(new Container());

        $response = $controller->status(new ApiCatalogSyncRunRepository($this->registry), new ArticleRepository($this->registry), $this->entityManager, $this->progress);
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('ERROR', $data['run']['status']);
        self::assertSame('error', $data['logs'][0]['type']);
        self::assertStringContainsString('interrompue', $data['logs'][0]['message']);
    }

    public function testAnEmptyRemoteCatalogueFinishesWithNoRemainingArticles(): void
    {
        $run = $this->synchronizer(new MockHttpClient([
            $this->response('item_families', 0, []),
            $this->response('items', 0, []),
        ]))->synchronize();
        $controller = new ApiCatalogSyncController();
        $controller->setContainer(new Container());

        $response = $controller->status(new ApiCatalogSyncRunRepository($this->registry), new ArticleRepository($this->registry), $this->entityManager, $this->progress);
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('DONE', $run->getStatus());
        self::assertSame(100, $data['progress']['percent']);
        self::assertSame(0, $data['progress']['remainingRows']);
        self::assertTrue($data['progress']['totalKnown']);
    }

    private function synchronizer(MockHttpClient $http): ApiCatalogSynchronizer
    {
        return new ApiCatalogSynchronizer(
            new ApiReceptionConnectionProvider(new ApiReceptionConnectionRepository($this->registry), $this->entityManager),
            new ApiReceptionClient($http, $this->cipher),
            new ApiCatalogRecordMapper(),
            new ArticleRepository($this->registry),
            $this->entityManager,
            new LockFactory(new InMemoryStore()),
            $this->progress,
        );
    }

    private function record(int $number): array
    {
        return ['data' => [
            'Id' => 'REF-' . $number,
            'UniqueId' => sprintf('00000000-0000-4000-8000-%012d', $number),
            'Caption' => 'Pièce ' . $number,
            'FamilyId' => null,
            'RealStock' => 3,
            'SalePriceVatIncluded' => '120.00',
            'SalePriceVatExcluded' => '100.00',
        ]];
    }

    private function response(string $resource, int $total, array $items): MockResponse
    {
        return new MockResponse(json_encode(['resource' => $resource, 'total' => $total, 'items' => $items], JSON_THROW_ON_ERROR));
    }
}
