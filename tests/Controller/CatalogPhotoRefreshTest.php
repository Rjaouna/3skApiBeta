<?php

namespace App\Tests\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Service\ApiReceptionConnectionProvider;
use App\Service\ApiReceptionCredentialCipher;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CatalogPhotoRefreshTest extends WebTestCase
{
    private const UID = '0B9989E8-AD66-4BB3-A4A1-034B578A880F';
    private $browser;
    private User $admin;
    private User $customer;
    private array $requests = [];
    private int $remoteStatus = 201;

    protected function setUp(): void
    {
        $this->browser = static::createClient();
        $this->browser->disableReboot();
        $container = static::getContainer();
        $container->set(HttpClientInterface::class, new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            if (str_ends_with($url, '/revision')) return new MockResponse('{"revision":"initial"}');
            $this->requests[] = [$method, $url, $options];

            return new MockResponse(json_encode(['ok' => true, 'item' => ['uniqueId' => self::UID]], JSON_THROW_ON_ERROR), ['http_code' => $this->remoteStatus]);
        }));
        $em = $container->get(EntityManagerInterface::class);
        self::assertInstanceOf(SQLitePlatform::class, $em->getConnection()->getDatabasePlatform());
        self::assertTrue($em->getConnection()->getParams()['memory'] ?? false);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $this->admin = (new User())->setEmail('photo-admin@example.invalid')->setPassword('unused')->setRoles(['ROLE_ADMIN']);
        $this->customer = (new User())->setEmail('photo-customer@example.invalid')->setPassword('unused')->setRoles(['ROLE_USER']);
        $em->persist($this->admin);
        $em->persist($this->customer);
        $em->persist((new Article())->setId('PHOTO-REF')->setUniqueId(self::UID));
        $connection = $container->get(ApiReceptionConnectionProvider::class)->get();
        $connection->setBaseUrl('https://api.example.test')->setApiKeyCiphertext($container->get(ApiReceptionCredentialCipher::class)->encrypt('server_only_fixture_key_1234567890123456'));
        $em->flush();
        // These HTTP tests inspect server-rendered permissions; module bundling is covered by browser QA.
        $container->get('twig')->addFunction(new \Twig\TwigFunction('importmap', static fn () => ''));
    }

    private function adminToken(): string
    {
        $this->browser->loginUser($this->admin);
        $crawler = $this->browser->request('GET', '/articles');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#catalog-photo-refresh-config');

        return json_decode($crawler->filter('#catalog-photo-refresh-config')->text(), true, flags: JSON_THROW_ON_ERROR)['token'];
    }

    private function post(string $token, string $uid = self::UID): array
    {
        $this->browser->request('POST', '/api/admin/catalog-photos/' . $uid . '/request', server: ['HTTP_X_CSRF_TOKEN' => $token, 'HTTP_ACCEPT' => 'application/json']);

        return json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function testAdminQueuesTheActualArticleAndKeepsApiKeyOnTheServer(): void
    {
        $data = $this->post($this->adminToken());
        self::assertResponseIsSuccessful();
        self::assertTrue($data['ok']);
        self::assertCount(1, $this->requests);
        self::assertSame('POST', $this->requests[0][0]);
        self::assertSame('https://api.example.test/api/v1/photo-requests', $this->requests[0][1]);
        self::assertSame(['uniqueId' => self::UID], json_decode($this->requests[0][2]['body'], true));
        self::assertStringNotContainsString('server_only_fixture', $this->browser->getResponse()->getContent());
        self::assertTrue($this->browser->getResponse()->headers->hasCacheControlDirective('no-store'));
    }

    public function testCustomerHasNoControlsAndCannotCallTheRoute(): void
    {
        $this->browser->loginUser($this->customer);
        $this->browser->request('GET', '/articles');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('#catalog-photo-refresh-config');
        self::assertStringNotContainsString('scripts/catalog-photo-refresh.js', $this->browser->getResponse()->getContent());
        $this->browser->request('POST', '/api/admin/catalog-photos/' . self::UID . '/request');
        self::assertResponseStatusCodeSame(403);
        self::assertCount(0, $this->requests);
    }

    public function testAdminNeedsValidCsrfAndAnExistingArticle(): void
    {
        $token = $this->adminToken();
        $this->post('bad');
        self::assertResponseStatusCodeSame(403);
        $this->post($token, '00000000-0000-4000-8000-000000000000');
        self::assertResponseStatusCodeSame(404);
        self::assertCount(0, $this->requests);
    }

    public function testRemoteFailureIsNotReportedAsARegisteredRequest(): void
    {
        $token = $this->adminToken();
        $this->remoteStatus = 503;
        $data = $this->post($token);
        self::assertResponseStatusCodeSame(502);
        self::assertFalse($data['ok']);
    }
}
