<?php

namespace App\Tests\Service;

use App\Entity\Article;
use App\Entity\ImportSource;
use App\Service\ArticleImageLocator;
use App\Service\CatalogAnomalyService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;

final class CatalogAnomalyServiceTest extends TestCase
{
    private const PRESENT_ID = '11111111-1111-1111-1111-111111111111';
    private const MISSING_ID = '22222222-2222-2222-2222-222222222222';
    private const ZERO_PRICE_ID = '33333333-3333-3333-3333-333333333333';
    private const PNG = "\x89PNG\r\n\x1a\nfixture";

    private string $root;
    private EntityManager $entityManager;
    private CatalogAnomalyService $service;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('This isolated database test requires pdo_sqlite.');
        }

        $this->root = sys_get_temp_dir() . '/3sk-catalog-anomaly-test-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/project/public/ebpsynch', 0777, true);
        mkdir($this->root . '/configured-export', 0777, true);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $configuration = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2) . '/src/Entity'], true);
        $this->entityManager = new EntityManager($connection, $configuration);

        $articleMetadata = $this->entityManager->getClassMetadata(Article::class);
        $articleMetadata->customRepositoryClassName = EntityRepository::class;
        $sourceMetadata = $this->entityManager->getClassMetadata(ImportSource::class);
        $sourceMetadata->customRepositoryClassName = EntityRepository::class;
        (new SchemaTool($this->entityManager))->createSchema([$articleMetadata, $sourceMetadata]);

        $this->entityManager->persist(
            (new ImportSource())
                ->setDirectoryPath($this->root . '/configured-export')
                ->setFileName('items.csv'),
        );
        $this->persistArticle(self::PRESENT_ID, 'PRESENT-REF', 'Pièce avec photo', '10.00');
        $this->persistArticle(self::MISSING_ID, 'MISSING-REF', 'Pièce sans photo', '10.00');
        $this->persistArticle(self::ZERO_PRICE_ID, 'ZERO-REF', 'Pièce à prix nul', '0.00');
        $this->entityManager->flush();

        file_put_contents($this->root . '/configured-export/' . self::PRESENT_ID . '.png', self::PNG);

        $this->service = new CatalogAnomalyService(
            $this->entityManager,
            new ArticleImageLocator($this->root . '/project'),
            new ArrayAdapter(),
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->entityManager->close();
            $this->entityManager->getConnection()->close();
        }

        if (isset($this->root)) {
            (new Filesystem())->remove($this->root);
        }
    }

    public function testItGroupsMissingImagesAndZeroPricesWithoutDoubleCountingArticles(): void
    {
        self::assertSame(
            ['total' => 2, 'missingImageCount' => 2, 'zeroPriceCount' => 1],
            $this->service->getSummary(),
        );

        $missingImagePage = $this->service->getPage(CatalogAnomalyService::TYPE_MISSING_IMAGE, 1, 50);
        self::assertSame(['MISSING-REF', 'ZERO-REF'], array_column($missingImagePage['items'], 'code'));
        self::assertTrue($missingImagePage['items'][1]['hasZeroPrice']);

        $zeroPricePage = $this->service->getPage(CatalogAnomalyService::TYPE_ZERO_PRICE, 1, 50);
        self::assertSame(['ZERO-REF'], array_column($zeroPricePage['items'], 'code'));
    }

    public function testCheckingAnImageInvalidatesTheSnapshotWhenTheFileIsPresent(): void
    {
        self::assertFalse($this->service->checkImage(self::MISSING_ID)['imageDetected']);

        file_put_contents($this->root . '/configured-export/' . self::MISSING_ID . '.png', self::PNG);

        self::assertTrue($this->service->checkImage(self::MISSING_ID)['imageDetected']);
        self::assertSame(1, $this->service->getSummary()['missingImageCount']);
    }

    private function persistArticle(string $uniqueId, string $code, string $designation, string $price): void
    {
        $this->entityManager->persist(
            (new Article())
                ->setUniqueId($uniqueId)
                ->setId($code)
                ->setDesComClear($designation)
                ->setSalePriceVatIncluded($price)
                ->setSysModifiedDate(new \DateTimeImmutable('2026-10-17 12:00:00')),
        );
    }
}
