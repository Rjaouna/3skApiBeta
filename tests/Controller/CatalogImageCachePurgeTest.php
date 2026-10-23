<?php

namespace App\Tests\Controller;

use App\Controller\Admin\CatalogImageConfigurationController;
use App\Entity\Article;
use App\Entity\CatalogImageConfiguration;
use App\Exception\ApiReceptionException;
use App\Repository\ArticleRepository;
use App\Repository\CatalogImageConfigurationRepository;
use App\Service\CatalogImageBaseUrlProvider;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class CatalogImageCachePurgeTest extends TestCase
{
    private EntityManager $entityManager;
    private CatalogImageConfigurationRepository $configurations;
    private ArrayAdapter $cache;
    private CatalogImageBaseUrlProvider $images;

    protected function setUp(): void
    {
        $configuration = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2) . '/src/Entity'], true);
        $this->entityManager = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]), $configuration);
        (new SchemaTool($this->entityManager))->createSchema([$this->entityManager->getClassMetadata(CatalogImageConfiguration::class)]);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);
        $this->configurations = new CatalogImageConfigurationRepository($registry);
        $this->cache = new ArrayAdapter();
        $this->images = $this->provider($this->cache);
        $this->images->getConfiguration()->setBaseUrl('https://api.example.test/images');
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        $this->entityManager->getConnection()->close();
    }

    public function testPurgeChangesAllImageUrlsAndPersistsAcrossRequestsWithoutClearingOtherCaches(): void
    {
        $item = $this->cache->getItem('unrelated_catalogue_data')->set('preserved');
        $this->cache->save($item);
        $before = $this->images->imageUrl('PHOTO-A');
        self::assertSame($before, $this->provider($this->cache)->imageUrl('PHOTO-A'));
        $version = $this->images->purgeCache();

        self::assertNotSame($before, $this->images->imageUrl('PHOTO-A'));
        self::assertStringEndsWith('?v=' . $version, $this->images->imageUrl('PHOTO-A'));
        self::assertStringEndsWith('?v=' . $version, $this->provider($this->cache)->imageUrl('PHOTO-B'));
        self::assertSame($this->images->imageUrl('PHOTO-A'), $this->provider($this->cache)->imageUrl('PHOTO-A'));
        self::assertSame('preserved', $this->cache->getItem('unrelated_catalogue_data')->get());
        self::assertNotSame($version, $this->images->purgeCache());
        self::assertSame('/local/PHOTO-A.png', parse_url($this->images->imageUrl('PHOTO-A', '/local/'), PHP_URL_PATH));
    }

    public function testEndpointRejectsAnInvalidCsrfTokenWithoutPurging(): void
    {
        $before = $this->images->cacheVersion();
        $response = $this->controller(false)->purge(Request::create('/admin/catalog-images/purge-cache', 'POST'), $this->images, $this->createStub(ArticleRepository::class));

        self::assertSame(403, $response->getStatusCode());
        self::assertSame($before, $this->images->cacheVersion());
    }

    public function testEndpointRefreshesPreviewsAndLeavesSourceFilesUntouched(): void
    {
        $file = tempnam(sys_get_temp_dir(), '3sk-photo-purge-');
        file_put_contents($file, "\x89PNG\r\n\x1a\noriginal photo");
        $beforeHash = hash_file('sha256', $file);
        $before = $this->images->imageUrl('PHOTO-A');
        $articles = $this->createStub(ArticleRepository::class);
        $articles->method('findBy')->willReturn([(new Article())->setUniqueId('PHOTO-A')->setId('REF-A')]);

        try {
            $response = $this->controller(true)->purge(Request::create('/admin/catalog-images/purge-cache', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"_token":"valid"}'), $this->images, $articles);
            $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

            self::assertSame(200, $response->getStatusCode());
            self::assertTrue($data['ok']);
            self::assertNotSame($before, $data['samples'][0]['url']);
            self::assertSame('https://api.example.test/images', $data['baseUrl']);
            self::assertSame($beforeHash, hash_file('sha256', $file));
            self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        } finally {
            unlink($file);
        }
    }

    public function testCacheWriteFailureIsReported(): void
    {
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($this->cache->getItem('write_failure'));
        $cache->method('save')->willReturn(false);

        $this->expectException(ApiReceptionException::class);
        $this->provider($cache)->purgeCache();
    }

    private function provider(CacheItemPoolInterface $cache): CatalogImageBaseUrlProvider
    {
        return new CatalogImageBaseUrlProvider($this->configurations, $this->entityManager, '/ebpsynch', $cache);
    }

    private function controller(bool $validToken): CatalogImageConfigurationController
    {
        $tokens = $this->createStub(CsrfTokenManagerInterface::class);
        $tokens->method('isTokenValid')->willReturn($validToken);
        $container = new Container();
        $container->set('security.csrf.token_manager', $tokens);
        $controller = new CatalogImageConfigurationController();
        $controller->setContainer($container);

        return $controller;
    }
}
