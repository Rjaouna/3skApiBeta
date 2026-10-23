<?php

namespace App\Tests\Service;

use App\Entity\ApiReceptionConnection;
use App\Repository\ApiReceptionConnectionRepository;
use App\Service\ApiReceptionClient;
use App\Service\ApiReceptionConnectionProvider;
use App\Service\ApiReceptionCredentialCipher;
use App\Service\CatalogPhotoPublicationVersion;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CatalogPhotoPublicationVersionTest extends TestCase
{
    public function testPublicationVersionIsSharedAndSurvivesNetworkFailures(): void
    {
        $cipher = new ApiReceptionCredentialCipher('photo-fixture-secret');
        $connection = (new ApiReceptionConnection())->setBaseUrl('https://api.example.test')->setApiKeyCiphertext($cipher->encrypt('fixture-key'));
        $config = \Doctrine\ORM\ORMSetup::createAttributeMetadataConfig([dirname(__DIR__, 2) . '/src/Entity'], true);
        $config->enableNativeLazyObjects(true);
        $orm = new \Doctrine\ORM\EntityManager(\Doctrine\DBAL\DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]), $config);
        (new \Doctrine\ORM\Tools\SchemaTool($orm))->createSchema([$orm->getClassMetadata(ApiReceptionConnection::class)]);
        $orm->persist($connection);
        $orm->flush();
        $registry = $this->createStub(\Doctrine\Persistence\ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($orm);
        $provider = new ApiReceptionConnectionProvider(new ApiReceptionConnectionRepository($registry), $orm);
        $cache = new ArrayAdapter();
        $revision = 'initial';
        $status = 200;
        $calls = 0;
        $client = new ApiReceptionClient(new MockHttpClient(function () use (&$revision, &$status, &$calls): MockResponse { ++$calls; return new MockResponse(json_encode(['revision' => $revision]), ['http_code' => $status]); }), $cipher);
        $current = fn () => new CatalogPhotoPublicationVersion($provider, $client, $cache);
        $first = $current();
        self::assertSame('initial', $first->current());
        self::assertSame('initial', $first->current());
        self::assertSame('initial', $current()->current());
        self::assertSame(1, $calls);
        $key = 'catalog_photos.publication.' . hash('sha256', $connection->getBaseUrl() . $connection->getApiKeyCiphertext());
        $cache->save($cache->getItem($key)->set(['revision' => 'initial', 'checkAfter' => 0]));
        $revision = str_repeat('a', 32);
        self::assertSame($revision, $current()->current());
        self::assertSame(2, $calls);
        $cache->save($cache->getItem($key)->set(['revision' => $revision, 'checkAfter' => 0]));
        $status = 503;
        self::assertSame($revision, $current()->current());
        self::assertSame($revision, $current()->current());
        self::assertSame(3, $calls);
        $orm->close();
        $orm->getConnection()->close();
    }
}
