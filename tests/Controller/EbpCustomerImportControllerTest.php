<?php

namespace App\Tests\Controller;

use App\Entity\ApiReceptionConnection;
use App\Entity\EbpCustomerImport;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ApiReceptionConnectionProvider;
use App\Service\ApiReceptionCredentialCipher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class EbpCustomerImportControllerTest extends WebTestCase
{
    private const UID = '10000000-0000-4000-8000-000000000001';
    private $browser;
    private EntityManagerInterface $entityManager;
    private User $admin;
    private array $requests = [];

    protected function setUp(): void
    {
        $this->browser = static::createClient();
        $this->browser->disableReboot();
        $container = static::getContainer();
        $container->set(HttpClientInterface::class, new MockHttpClient(function (string $method, string $url): MockResponse {
            $this->requests[] = [$method, $url];
            if ($method === 'POST' && str_ends_with($url, '/api/v1/customer-imports')) {
                return new MockResponse(json_encode([
                    'ok' => true,
                    'item' => ['id' => 42, 'uniqueId' => self::UID, 'status' => 'pending'],
                ], JSON_THROW_ON_ERROR), ['http_code' => 201]);
            }
            if ($method === 'GET' && str_ends_with($url, '/api/v1/customer-imports/42')) {
                return new MockResponse(json_encode([
                    'ok' => true,
                    'item' => [
                        'id' => 42,
                        'uniqueId' => self::UID,
                        'status' => 'completed',
                        'customer' => [
                            'uniqueId' => self::UID,
                            'ebpId' => 'CLI-42',
                            'customerCode' => self::UID,
                            'companyName' => 'Garage Martin',
                            'email' => 'garage.martin@example.test',
                            'phone' => '0600000000',
                            'address1' => '1 rue EBP',
                            'address2' => null,
                            'city' => 'Casablanca',
                            'country' => 'Maroc',
                        ],
                    ],
                ], JSON_THROW_ON_ERROR));
            }

            return new MockResponse('{"ok":true,"removed":true,"cleared":1}');
        }));
        $this->entityManager = $container->get(EntityManagerInterface::class);
        (new SchemaTool($this->entityManager))->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
        $this->admin = (new User())->setEmail('admin-import@example.test')->setPassword('unused')->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($this->admin);
        $connection = $container->get(ApiReceptionConnectionProvider::class)->get();
        $connection->setBaseUrl('https://api.example.test')->setApiKeyCiphertext($container->get(ApiReceptionCredentialCipher::class)->encrypt('server_only_fixture_key_1234567890123456'));
        $this->entityManager->flush();
    }

    private function csrfToken(): string
    {
        $this->browser->loginUser($this->admin);
        $token = 'fixture-admin-clients-csrf-token';
        $session = $this->browser->getSession();
        self::assertNotNull($session);
        $session->set('_csrf/admin_clients_crud', $token);
        $session->save();

        return $token;
    }

    /** @return array<string, mixed> */
    private function post(string $url, array $body): array
    {
        $this->browser->request('POST', $url, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($body, JSON_THROW_ON_ERROR));

        return json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function testAdminRequestsAndCreatesAnEbpCustomerAccountThroughAjaxEndpoints(): void
    {
        $token = $this->csrfToken();
        $created = $this->post('/admin/clients/ebp-imports', ['_token' => $token, 'uniqueId' => self::UID]);
        self::assertResponseStatusCodeSame(201);
        self::assertTrue($created['ok']);
        self::assertSame('pending', $created['item']['status']);

        $refreshed = $this->post('/admin/clients/ebp-imports/refresh', ['_token' => $token]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $refreshed['summary']['created']);
        self::assertSame('created', $refreshed['items'][0]['status']);

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['ebpUniqueId' => self::UID]);
        self::assertInstanceOf(User::class, $user);
        self::assertSame('garage.martin@example.test', $user->getEmail());
        self::assertSame('Garage Martin', $user->getCompanyName());
        self::assertSame('CLI-42', $user->getCustomerCode());
        self::assertNotNull($user->getCreatedAt());
        self::assertNotNull($user->getUpdatedAt());
        self::assertNotSame('unused', $user->getPassword());
        self::assertContains(['POST', 'https://api.example.test/api/v1/customer-imports'], $this->requests);
        self::assertContains(['GET', 'https://api.example.test/api/v1/customer-imports/42'], $this->requests);

        $this->browser->request('GET', '/admin/clients/list');
        $clientRows = json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['items'];
        $clientRow = array_values(array_filter($clientRows, static fn (array $row): bool => $row['email'] === 'garage.martin@example.test'))[0];
        self::assertSame('EBP', $clientRow['source']);
        self::assertNotEmpty($clientRow['createdAt']);
        self::assertNotEmpty($clientRow['updatedAt']);

        $siteCustomer = (new User())->setEmail('site-customer@example.test')->setPassword('unused')->setRoles(['ROLE_USER']);
        $this->entityManager->persist($siteCustomer);
        $this->entityManager->flush();
        $this->browser->request('GET', '/admin/clients/list');
        $clientRows = json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['items'];
        $siteRow = array_values(array_filter($clientRows, static fn (array $row): bool => $row['email'] === 'site-customer@example.test'))[0];
        self::assertSame('Site', $siteRow['source']);

        // Le même UniqueId peut être relancé pour corriger une ancienne fiche
        // sans créer un second compte site.
        $reimported = $this->post('/admin/clients/ebp-imports', ['_token' => $token, 'uniqueId' => self::UID]);
        self::assertResponseStatusCodeSame(201);
        self::assertTrue($reimported['ok']);
        $refreshed = $this->post('/admin/clients/ebp-imports/refresh', ['_token' => $token]);
        self::assertSame(0, $refreshed['summary']['created']);
        self::assertSame(1, $refreshed['summary']['updated']);
        self::assertSame('CLI-42', static::getContainer()->get(UserRepository::class)->findOneBy(['ebpUniqueId' => self::UID])?->getCustomerCode());
    }

    public function testAnImportRequestCanBeRemovedAndTheListCanBeCleared(): void
    {
        $token = $this->csrfToken();
        $this->post('/admin/clients/ebp-imports', ['_token' => $token, 'uniqueId' => self::UID]);
        $import = $this->entityManager->getRepository(EbpCustomerImport::class)->findOneBy(['uniqueId' => self::UID]);
        self::assertInstanceOf(EbpCustomerImport::class, $import);
        $deleted = $this->post('/admin/clients/ebp-imports/' . $import->getId(), ['_token' => $token]);
        self::assertTrue($deleted['ok']);
        self::assertNull($this->entityManager->getRepository(EbpCustomerImport::class)->find($import->getId()));

        $this->post('/admin/clients/ebp-imports', ['_token' => $token, 'uniqueId' => self::UID]);
        $cleared = $this->post('/admin/clients/ebp-imports/clear/all', ['_token' => $token]);
        self::assertTrue($cleared['ok']);
        self::assertSame(1, $cleared['cleared']);
    }

    public function testCustomerCannotAccessTheAjaxEndpoints(): void
    {
        $customer = (new User())->setEmail('customer-import@example.test')->setPassword('unused')->setRoles(['ROLE_USER']);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();
        $this->browser->loginUser($customer);
        $this->browser->request('GET', '/admin/clients/ebp-imports');
        self::assertResponseStatusCodeSame(403);
    }
}
