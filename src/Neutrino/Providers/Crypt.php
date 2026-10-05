<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Config\Config;
use Phalcon\Encryption\Crypt as PhalconCrypt;

/**
 * The `crypt` service: `app.cipher` (default `aes-256-cfb`), `app.key`, and `app.crypt_signing` (default `true`).
 *
 * Nucleon 1.3 did not sign: its encrypted data is only readable with `app.crypt_signing = false`.
 */
class Crypt extends Provider
{
    protected string $name = Services::CRYPT;

    protected bool $shared = true;

    protected array $aliases = [PhalconCrypt::class];

    protected function register(): PhalconCrypt
    {
        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);

        $cipher = $config->path('app.cipher');
        $key = $config->path('app.key');
        $signing = $config->path('app.crypt_signing', true);

        $crypt = new PhalconCrypt(is_string($cipher) && $cipher !== '' ? $cipher : PhalconCrypt::DEFAULT_CIPHER, (bool) $signing);

        if (is_string($key) && $key !== '') {
            $crypt->setKey($key);
        }

        return $crypt;
    }
}
