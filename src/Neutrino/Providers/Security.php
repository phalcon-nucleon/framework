<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;
use Phalcon\Encryption\Security as PhalconSecurity;

/**
 * The `security` service. It reads the session and the request from the container when it needs them (CSRF
 * tokens): hashing a password does not start the session.
 */
class Security extends SimpleProvider
{
    protected string $class = PhalconSecurity::class;

    protected string $name = Services::SECURITY;

    protected bool $shared = true;

    protected array $aliases = [PhalconSecurity::class];
}
