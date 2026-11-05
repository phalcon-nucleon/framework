<?php

declare(strict_types=1);

namespace Test\Providers;

use Neutrino\Constants\Services;
use Neutrino\Providers\Crypt;
use Phalcon\Encryption\Crypt as PhalconCrypt;
use Phalcon\Encryption\Crypt\Exception\Mismatch;
use PHPUnit\Framework\Attributes\DataProvider;

final class CryptProviderTest extends ProvidersTestCase
{
    private const string KEY = 'Nucleon-1.3-key-32-bytes-long!!!';

    public function testService(): void
    {
        $di = $this->container([Crypt::class], ['app' => ['key' => self::KEY, 'cipher' => 'aes-256-cbc']]);
        $crypt = $di->getShared(Services::CRYPT);

        $this->assertInstanceOf(PhalconCrypt::class, $crypt);
        $this->assertSame($crypt, $di->getShared(PhalconCrypt::class));
        $this->assertSame('aes-256-cbc', $crypt->getCipher());
        $this->assertSame(self::KEY, $crypt->getKey());
        $this->assertSame('secret', $crypt->decryptBase64($crypt->encryptBase64('secret')));
    }

    public function testDefaults(): void
    {
        $crypt = $this->container([Crypt::class])->getShared(Services::CRYPT);

        $this->assertSame(PhalconCrypt::DEFAULT_CIPHER, $crypt->getCipher());
    }

    public function testSigningIsEnabledByDefault(): void
    {
        $signed = $this->crypt(true)->encryptBase64('secret');
        $unsigned = $this->crypt(false)->encryptBase64('secret');

        $this->assertSame('secret', $this->crypt(null)->decryptBase64($signed));

        $this->expectException(Mismatch::class);
        $this->crypt(null)->decryptBase64($unsigned);
    }

    /**
     * Values encrypted by Nucleon 1.3 (Phalcon 3.4): see fixtures/crypt-1.3.php.
     *
     * @return iterable<string, array{string, bool, bool, string, string}>
     */
    public static function legacyValues(): iterable
    {
        /** @var array{key: string, values: list<array{cipher: string, signing: bool, plain: string, base64: string, base64_safe: string}>} $fixture */
        $fixture = json_decode((string) file_get_contents(__DIR__ . '/fixtures/crypt-1.3.json'), true);

        foreach ($fixture['values'] as $value) {
            foreach (['base64' => false, 'base64_safe' => true] as $encoding => $safe) {
                $name = $value['cipher'] . ($value['signing'] ? ' signed ' : ' ') . $encoding;

                yield $name => [$value['cipher'], $value['signing'], $safe, $value[$encoding], $value['plain']];
            }
        }
    }

    #[DataProvider('legacyValues')]
    public function testDecryptsTheValuesOfNucleon13(string $cipher, bool $signed, bool $safe, string $encrypted, string $plain): void
    {
        $crypt = $this->container([Crypt::class], ['app' => ['key' => self::KEY, 'cipher' => $cipher, 'crypt_signing' => $signed]])
            ->getShared(Services::CRYPT);

        $this->assertSame($plain, $crypt->decryptBase64($encrypted, null, $safe));
    }

    /**
     * Nucleon 1.3 did not sign.
     *
     * @return iterable<string, array{string, bool, bool, string, string}>
     */
    public static function legacyUnsignedValues(): iterable
    {
        foreach (self::legacyValues() as $name => $value) {
            if (!$value[1]) {
                yield $name => $value;
            }
        }
    }

    #[DataProvider('legacyUnsignedValues')]
    public function testUnsignedValuesOfNucleon13NeedTheSigningDisabled(string $cipher, bool $signed, bool $safe, string $encrypted): void
    {
        $crypt = $this->container([Crypt::class], ['app' => ['key' => self::KEY, 'cipher' => $cipher]])->getShared(Services::CRYPT);

        $this->expectException(Mismatch::class);
        $crypt->decryptBase64($encrypted, null, $safe);
    }

    private function crypt(?bool $signing): PhalconCrypt
    {
        $app = ['key' => self::KEY] + ($signing === null ? [] : ['crypt_signing' => $signing]);

        return $this->container([Crypt::class], ['app' => $app])->getShared(Services::CRYPT);
    }
}
