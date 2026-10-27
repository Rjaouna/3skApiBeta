<?php

namespace App\Tests\Controller;

use App\Entity\ApiReceptionConnection;
use App\Entity\CustomerInvoice;
use App\Entity\User;
use App\Repository\CustomerInvoiceRepository;
use App\Service\ApiReceptionConnectionProvider;
use App\Service\ApiReceptionCredentialCipher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class InvoiceControllerTest extends WebTestCase
{
    private const UID = '10000000-0000-4000-8000-000000000001';
    private $browser;
    private EntityManagerInterface $em;
    private User $user;
    /** @var array<string, mixed> */
    private array $invoiceImportRequest = [];

    protected function setUp(): void
    {
        $this->browser = static::createClient();
        $this->browser->disableReboot();
        $container = static::getContainer();
        $container->set(HttpClientInterface::class, new MockHttpClient(function (string $method, string $url, array $options = []): MockResponse {
            if ($method === 'POST' && str_ends_with($url, '/api/v1/invoice-imports')) {
                $this->invoiceImportRequest = is_array($options['json'] ?? null) ? $options['json'] : [];
                return new MockResponse(json_encode(['ok' => true, 'item' => [
                    'id' => 74,
                    'uniqueId' => self::UID,
                    'status' => 'pending',
                    'fromDate' => $this->invoiceImportRequest['fromDate'] ?? null,
                    'toDate' => $this->invoiceImportRequest['toDate'] ?? null,
                ]], JSON_THROW_ON_ERROR), ['http_code' => 201]);
            }
            if ($method === 'GET' && str_ends_with($url, '/api/v1/invoice-imports/74')) {
                return new MockResponse(json_encode(['ok' => true, 'item' => [
                    'id' => 74, 'uniqueId' => self::UID, 'status' => 'completed', 'invoiceCount' => 1,
                    'fromDate' => $this->invoiceImportRequest['fromDate'] ?? null,
                    'toDate' => $this->invoiceImportRequest['toDate'] ?? null,
                    'invoices' => [[
                        'sourceId' => 'FA-74', 'customerUniqueId' => self::UID, 'documentNumber' => 'FA2026-0074',
                        'documentType' => '2', 'documentKind' => 'invoice', 'documentDate' => '2026-10-23T10:00:00+00:00', 'state' => '1',
                        'dueDate' => '2026-11-23T10:00:00+00:00', 'totalTtc' => '450.20', 'totalHt' => '375.17',
                        'billing' => ['companyName' => 'Garage EBP', 'customerCode' => 'CLI-74', 'email' => 'ebp-client@example.test', 'address1' => '74 rue EBP', 'city' => 'Casablanca'],
                        'lines' => [['sourceId' => 'FA-74-1', 'itemId' => 'ART-74', 'designation' => 'Filtre à huile', 'quantity' => '2', 'unitPriceHt' => '187.58', 'unitPriceTtc' => '225.10', 'totalHt' => '375.17', 'totalTtc' => '450.20', 'vatRate' => '20']],
                    ]],
                ]], JSON_THROW_ON_ERROR));
            }

            return new MockResponse('{}', ['http_code' => 404]);
        }));
        $this->em = $container->get(EntityManagerInterface::class);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->user = (new User())->setEmail('invoice.customer@example.test')->setPassword('unused')->setRoles(['ROLE_USER'])->setIsActive(true)->setEbpUniqueId(self::UID);
        $this->em->persist($this->user);
        $connection = $container->get(ApiReceptionConnectionProvider::class)->get();
        $connection->setBaseUrl('https://api.example.test')->setApiKeyCiphertext($container->get(ApiReceptionCredentialCipher::class)->encrypt('server_only_fixture_key_1234567890123456'));
        $this->em->flush();
    }

    public function testCustomerRequestsAndSeesOnlyTheirInvoices(): void
    {
        $this->browser->loginUser($this->user);
        $token = 'invoice-sync-csrf';
        $session = $this->browser->getSession();
        self::assertNotNull($session);
        $session->set('_csrf/customer_invoice_sync', $token);
        $session->save();
        $outsideRequestedPeriod = new CustomerInvoice($this->user, [
            'sourceId' => 'primary:FA-2025',
            'documentNumber' => 'FA2025-0001',
            'documentType' => '2',
            'documentKind' => 'invoice',
            'documentDate' => '2025-12-31T10:00:00+00:00',
            'state' => 'Validé',
            'totalTtc' => '100.00',
            'lines' => [],
        ]);
        $this->em->persist($outsideRequestedPeriod);
        $this->em->flush();

        $this->browser->request('POST', '/mes-factures/refresh', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['_token' => $token, 'fromDate' => '2026-01-01', 'toDate' => '2026-12-31'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(202);
        $body = json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertTrue($body['ok']);
        self::assertSame('completed', $body['status']['status']);
        self::assertSame(['uniqueId' => self::UID, 'fromDate' => '2026-01-01', 'toDate' => '2026-12-31'], $this->invoiceImportRequest);
        self::assertSame('2026-01-01', $body['status']['fromDate']);
        self::assertSame('2026-12-31', $body['status']['toDate']);
        $invoice = static::getContainer()->get(CustomerInvoiceRepository::class)->findOneBy(['customer' => $this->user]);
        self::assertInstanceOf(CustomerInvoice::class, $invoice);
        self::assertSame('FA2026-0074', $invoice->getDocumentNumber());
        self::assertCount(1, $invoice->getLines());
        self::assertSame('2026-11-23', $invoice->getDueDate()?->format('Y-m-d'));
        self::assertSame('Facture', $invoice->getDocumentLabel());
        self::assertSame('Garage EBP', $invoice->getBilling()['companyName']);
        self::assertSame('CLI-74', $invoice->getBilling()['customerCode']);
        self::assertInstanceOf(CustomerInvoice::class, static::getContainer()->get(CustomerInvoiceRepository::class)->findOneBy(['customer' => $this->user, 'sourceId' => 'primary:FA-2025']));

        $this->browser->request('GET', '/mes-factures/status');
        self::assertResponseIsSuccessful();
        $status = json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(2, $status['documentCount']);
        self::assertNotEmpty($status['status']['lastSyncedAt']);

        $this->browser->request('GET', '/mes-factures/' . $invoice->getId() . '/pdf');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-', $this->browser->getResponse()->getContent());
    }

    public function testInvoiceListSearchesLineDesignationsAndPaginates(): void
    {
        $this->browser->loginUser($this->user);
        for ($number = 1; $number <= 25; ++$number) {
            $this->em->persist(new CustomerInvoice($this->user, [
                'sourceId' => 'primary:FA-' . $number,
                'documentNumber' => 'FA2026-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'documentType' => 'Facture',
                'documentDate' => sprintf('2026-10-%02dT10:00:00+00:00', min($number, 28)),
                'state' => 'Validée',
                'totalHt' => '100.00',
                'totalTtc' => '120.00',
                'lines' => [['sourceId' => 'L-' . $number, 'itemId' => 'ART-' . $number, 'designation' => $number === 17 ? 'Amortisseur avant' : 'Filtre standard', 'quantity' => '1']],
            ]));
        }
        $creditNote = new CustomerInvoice($this->user, [
            // A legacy payload without documentKind still has to recognize the EBP subtype 3.
            'sourceId' => 'primary:AV-1', 'documentNumber' => 'AV2026-0001', 'documentType' => '3',
            'documentDate' => (new \DateTimeImmutable())->format(DATE_ATOM), 'state' => 'Validé', 'totalHt' => '-100.00', 'totalTtc' => '-120.00',
            'lines' => [['sourceId' => 'AV-1-1', 'itemId' => 'ART-AV', 'designation' => 'Article remboursé', 'quantity' => '1', 'totalTtc' => '-120.00']],
        ]);
        $deliveryNote = new CustomerInvoice($this->user, [
            'sourceId' => 'primary:BL-1', 'documentNumber' => 'BL2026-0001', 'documentType' => '6',
            'documentDate' => (new \DateTimeImmutable())->format(DATE_ATOM), 'state' => 'Validé', 'totalHt' => '100.00', 'totalTtc' => '120.00',
            'lines' => [['sourceId' => 'BL-1-1', 'itemId' => 'ART-BL', 'designation' => 'Pièce livrée', 'quantity' => '1', 'totalTtc' => '120.00']],
        ]);
        $this->em->persist($creditNote);
        $this->em->persist($deliveryNote);
        $this->em->flush();

        $this->browser->request('GET', '/mes-factures/liste?q=amortisseur');
        self::assertResponseIsSuccessful();
        $searched = json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(1, $searched['total']);
        self::assertStringContainsString('FA2026-0017', $searched['html']);

        $this->browser->request('GET', '/mes-factures/liste?q=avoir');
        self::assertResponseIsSuccessful();
        $credits = json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(1, $credits['total']);
        self::assertStringContainsString('AV2026-0001', $credits['html']);
        self::assertStringContainsString('Avoir EBP', $credits['html']);
        self::assertStringContainsString('invoice-document-badge--credit_note', $credits['html']);
        self::assertStringContainsString('invoice-new-badge', $credits['html']);

        $this->browser->request('GET', '/mes-factures/liste?type=delivery_note');
        self::assertResponseIsSuccessful();
        $deliveries = json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(1, $deliveries['total']);
        self::assertStringContainsString('BL2026-0001', $deliveries['html']);
        self::assertStringContainsString('Bon de livraison', $deliveries['html']);
        self::assertStringContainsString('invoice-document-badge--delivery_note', $deliveries['html']);
        self::assertStringContainsString('Bons de livraison', $deliveries['statisticsHtml']);
        self::assertStringContainsString('Total factures', $deliveries['statisticsHtml']);
        self::assertStringContainsString('Total avoirs', $deliveries['statisticsHtml']);
        self::assertStringContainsString('Net des documents', $deliveries['statisticsHtml']);

        $this->browser->request('GET', '/mes-factures/' . $creditNote->getId() . '/pdf');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('AVOIR', $this->browser->getResponse()->getContent());

        $this->browser->request('GET', '/mes-factures/liste?page=2');
        self::assertResponseIsSuccessful();
        $secondPage = json_decode($this->browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(27, $secondPage['total']);
        self::assertSame(2, $secondPage['page']);
        self::assertSame(10, substr_count($secondPage['html'], '<div class="invoice-number">'));
    }
}
