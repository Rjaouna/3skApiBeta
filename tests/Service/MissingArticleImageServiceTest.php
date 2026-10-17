<?php

namespace App\Tests\Service;

use App\Entity\Article;
use App\Entity\ImportSource;
use App\Service\ArticleImageLocator;
use App\Service\MissingArticleImageService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;

final class MissingArticleImageServiceTest extends TestCase
{
    private const PRESENT_ID = '11111111-1111-1111-1111-111111111111';
    private const MISSING_ID = '22222222-2222-2222-2222-222222222222';
    private const PNG = "\x89PNG\r\n\x1a\nfixture";

    private string $root;
    private EntityManager $entityManager;
    private MissingArticleImageService $service;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('This isolated database test requires pdo_sqlite.');
        }

        $this->root = sys_get_temp_dir() . '/3sk-missing-image-test-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/project/public/ebpsynch', 0777, true);
        mkdir($this->root . '/project/export_images', 0777, true);
        mkdir($this->root . '/configured-export', 0777, true);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $configuration = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2) . '/src/Entity'], true);
        $this->entityManager = new EntityManager($connection, $configuration);

        $articleMetadata = $this->entityManager->getClassMetadata(Article::class);
        $articleMetadata->customRepositoryClassName = EntityRepository::class;
        $sourceMetadata = $this->entityManager->getClassMetadata(ImportSource::class);
        $sourceMetadata->customRepositoryClassName = EntityRepository::class;
        (new SchemaTool($this->entityManager))->createSchema([$articleMetadata, $sourceMetadata]);

        $source = (new ImportSource())
            ->setDirectoryPath($this->root . '/configured-export')
            ->setFileName('items.csv');
        $this->entityManager->persist($source);

        $this->persistArticle(self::PRESENT_ID, 'PRESENT-REF', 'Pièce avec photo');
        $this->persistArticle(self::MISSING_ID, 'MISSING-REF', 'Pièce sans photo');
        $this->entityManager->flush();

        file_put_contents($this->root . '/configured-export/' . self::PRESENT_ID . '.png', self::PNG);

        $this->service = new MissingArticleImageService(
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

    public function testExistingImagesAreExcludedAndNewlyDetectedImagesInvalidateTheCachedList(): void
    {
        $page = $this->service->getPage(1, 50);

        self::assertSame(1, $page['total']);
        self::assertSame(['MISSING-REF'], array_column($page['items'], 'code'));
        self::assertFalse($this->service->check(self::MISSING_ID));

        file_put_contents($this->root . '/configured-export/' . self::MISSING_ID . '.png', self::PNG);

        self::assertTrue($this->service->check(self::MISSING_ID));
        self::assertSame([], $this->service->getMissingArticles());
    }

    private function persistArticle(string $uniqueId, string $code, string $designation): void
    {
        $this->entityManager->persist(
            (new Article())
                ->setUniqueId($uniqueId)
                ->setId($code)
                ->setDesComClear($designation)
                ->setSysModifiedDate(new \DateTimeImmutable('now')),
        );
    }
}
