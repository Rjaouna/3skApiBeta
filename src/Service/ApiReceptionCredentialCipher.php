<?php

namespace App\Service;

use App\Exception\ApiReceptionException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Encrypts the server-to-server API key before it is persisted in MySQL. */
final class ApiReceptionCredentialCipher
{
    private const CIPHER = 'aes-256-gcm';
    private const VERSION = 'v1.';

    public function __construct(
        #[Autowire('%kernel.secret%')]
        private readonly string $appSecret,
    ) {
    }

    public function encrypt(string $plainText): string
    {
        $this->assertAvailable();
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLength);
        $tag = '';
        $cipherText = openssl_encrypt($plainText, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($cipherText === false || strlen($tag) !== 16) {
            throw new ApiReceptionException('La clé API ne peut pas être protégée sur ce serveur.');
        }

        return self::VERSION . $this->base64UrlEncode($iv . $tag . $cipherText);
    }

    public function decrypt(string $payload): string
    {
        $this->assertAvailable();
        if (!str_starts_with($payload, self::VERSION)) {
            throw new ApiReceptionException('La clé API enregistrée est dans un format inconnu. Enregistrez-la à nouveau.');
        }

        $decoded = $this->base64UrlDecode(substr($payload, strlen(self::VERSION)));
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($decoded === null || strlen($decoded) <= $ivLength + 16) {
            throw new ApiReceptionException('La clé API enregistrée est invalide. Enregistrez-la à nouveau.');
        }

        $iv = substr($decoded, 0, $ivLength);
        $tag = substr($decoded, $ivLength, 16);
        $cipherText = substr($decoded, $ivLength + 16);
        $plainText = openssl_decrypt($cipherText, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag);

        if (!is_string($plainText) || $plainText === '') {
            throw new ApiReceptionException('La clé API ne peut pas être déchiffrée. Vérifiez APP_SECRET puis enregistrez-la à nouveau.');
        }

        return $plainText;
    }

    private function assertAvailable(): void
    {
        if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt') || $this->appSecret === '') {
            throw new ApiReceptionException('Le chiffrement de la clé API n’est pas disponible sur ce serveur.');
        }
    }

    private function key(): string
    {
        return hash('sha256', $this->appSecret, true);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
