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
}
