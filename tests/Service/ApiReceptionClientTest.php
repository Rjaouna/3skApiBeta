<?php

namespace App\Tests\Service;

use App\Entity\ApiReceptionConnection;
use App\Exception\ApiReceptionException;
use App\Service\ApiCatalogFamilyKey;
use App\Service\ApiReceptionClient;
use App\Service\ApiReceptionCredentialCipher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ApiReceptionClientTest extends TestCase
{
    public function testItValidatesApiReceptAndReadsTheArticleEndpoint(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setApiKeyCiphertext($cipher->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz'));
        $http = new MockHttpClient([
            new MockResponse('{"status":"ok","service":"api-recept","time":"2026-10-18T10:00:00+00:00"}'),
            new MockResponse('{"resource":"items","total":1,"items":[{"data":{"Id":"REF-1"}}]}'),
        ]);

        $result = (new ApiReceptionClient($http, $cipher))->test($connection);

        self::assertSame('2026-10-18T10:00:00+00:00', $result['remoteTime']);
        self::assertGreaterThanOrEqual(0, $result['latencyMs']);
    }

    public function testItRejectsAnotherServiceOnTheConfiguredUrl(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setApiKeyCiphertext($cipher->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz'));

        $this->expectException(ApiReceptionException::class);
        $this->expectExceptionMessage('ApiRecept valide');

        (new ApiReceptionClient(new MockHttpClient([
            new MockResponse('{"status":"ok","service":"another-service"}'),
        ]), $cipher))->test($connection);
    }

    public function testItBuildsTheItemFamilyMapFromTheApi(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setApiKeyCiphertext($cipher->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz'));
        $client = new ApiReceptionClient(new MockHttpClient([
            new MockResponse('{"resource":"item_families","total":2,"items":[{"data":{"Id":"FREINAGE","Caption":"Freinage"}},{"data":{"Id":"FILTRATION","Caption":"Filtration"}}]}'),
        ]), $cipher);

        $families = $client->itemFamilyNames($connection);
        self::assertSame('Freinage', $families['FREINAGE']);
        self::assertSame('Filtration', $families['FILTRATION']);
    }

    public function testItIndexesNumericFamilyIdentifiersWithoutLeadingZeros(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setApiKeyCiphertext($cipher->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz'));
        $client = new ApiReceptionClient(new MockHttpClient([
            new MockResponse('{"resource":"item_families","total":1,"items":[{"data":{"Id":11,"Caption":"Freinage"}}]}'),
        ]), $cipher);

        $families = $client->itemFamilyNames($connection);
        self::assertSame('Freinage', $families[ApiCatalogFamilyKey::numericAlias('011')]);
    }

    public function testItPrefersTheCanonicalFamilyOverAnOldSourceNamespacedRecord(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setApiKeyCiphertext($cipher->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz'));
        $client = new ApiReceptionClient(new MockHttpClient([
            new MockResponse('{"resource":"item_families","total":2,"items":[{"sourceId":"e1a2b3c4d5e6f7a8:FAR00005","data":{"Id":"FAR00005","Caption":"Ancien libellé"}},{"sourceId":"FAR00005","data":{"Id":"FAR00005","Caption":"Libellé catalogue"}}]}'),
        ]), $cipher);

        self::assertSame('Libellé catalogue', $client->itemFamilyNames($connection)['FAR00005']);
    }

    public function testItIndexesEachSourceSpecificFamilyKey(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setApiKeyCiphertext($cipher->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz'));
        $client = new ApiReceptionClient(new MockHttpClient([
            new MockResponse('{"resource":"item_families","total":2,"items":[{"sourceId":"aaaaaaaaaaaaaaaa:FAR00005","data":{"Id":"FAR00005","Caption":"Famille A"}},{"sourceId":"bbbbbbbbbbbbbbbb:FAR00005","data":{"Id":"FAR00005","Caption":"Famille B"}}]}'),
        ]), $cipher);

        $families = $client->itemFamilyNames($connection);
        self::assertSame('Famille A', $families['aaaaaaaaaaaaaaaa:FAR00005']);
        self::assertSame('Famille B', $families['bbbbbbbbbbbbbbbb:FAR00005']);
    }

    public function testItValidatesAndReadsABoundedCatalogChangePage(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setApiKeyCiphertext($cipher->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz'));
        $client = new ApiReceptionClient(new MockHttpClient([
            new MockResponse(json_encode([
                'through' => 42,
                'nextCursor' => 42,
                'total' => 1,
                'hasMore' => false,
                'items' => [[
                    'revision' => 42,
                    'resource' => 'items',
                    'sourceId' => '00000000-0000-4000-8000-000000000042',
                    'operation' => 'upsert',
                    'data' => ['Id' => 'REF-42'],
                ]],
            ], JSON_THROW_ON_ERROR)),
        ]), $cipher);

        $page = $client->catalogChanges($connection, 41, 42);

        self::assertSame(42, $page['through']);
        self::assertSame(42, $page['nextCursor']);
        self::assertSame('items', $page['items'][0]['resource']);
    }

    public function testItKeepsWorkingWithAnApiReceptBeforeTheChangeFeedWasDeployed(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setApiKeyCiphertext($cipher->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz'));
        $client = new ApiReceptionClient(new MockHttpClient([
            new MockResponse('{"error":"Not found"}', ['http_code' => 404]),
        ]), $cipher);

        self::assertNull($client->catalogHead($connection));
    }

    public function testItRetriesATransientCatalogReadBeforeFailing(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $connection = (new ApiReceptionConnection())
            ->setBaseUrl('https://api.example.test')
            ->setApiKeyCiphertext($cipher->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz'));
        $retries = [];
        $client = new ApiReceptionClient(new MockHttpClient([
            new MockResponse('{"error":"Temporarily unavailable"}', ['http_code' => 503]),
            new MockResponse('{"through":42,"nextCursor":42,"total":0,"hasMore":false,"items":[]}'),
        ]), $cipher);

        self::assertSame(42, $client->catalogHead($connection, static function (int $retry, int $maxAttempts, int $delayMs) use (&$retries): void {
            $retries[] = [$retry, $maxAttempts, $delayMs];
        }));
        self::assertSame([[1, 4, 500]], $retries);
    }
}
