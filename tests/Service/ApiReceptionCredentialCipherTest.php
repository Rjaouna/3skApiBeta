<?php

namespace App\Tests\Service;

use App\Exception\ApiReceptionException;
use App\Service\ApiReceptionCredentialCipher;
use PHPUnit\Framework\TestCase;

final class ApiReceptionCredentialCipherTest extends TestCase
{
    public function testItEncryptsAndDecryptsTheApiKey(): void
    {
        $cipher = new ApiReceptionCredentialCipher('test-application-secret');
        $apiKey = 'apr_live_0123456789abcdefghijklmnopqrstuvwxyz';

        $encrypted = $cipher->encrypt($apiKey);

        self::assertStringStartsWith('v1.', $encrypted);
        self::assertStringNotContainsString($apiKey, $encrypted);
        self::assertSame($apiKey, $cipher->decrypt($encrypted));
    }

    public function testItRefusesACiphertextFromAnotherApplicationSecret(): void
    {
        $encrypted = (new ApiReceptionCredentialCipher('first-secret'))->encrypt('apr_live_0123456789abcdefghijklmnopqrstuvwxyz');

        $this->expectException(ApiReceptionException::class);
        (new ApiReceptionCredentialCipher('second-secret'))->decrypt($encrypted);
    }
}
