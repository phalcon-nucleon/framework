<?php

namespace Neutrino\Providers;

use Neutrino\Auth\Manager as AuthManager;
use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;

/**
 * Class Auth
 *
 *  @package Neutrino\Providers
 */
class Auth extends SimpleProvider
{
    protected string $class = AuthManager::class;

    protected string $name = Services::AUTH;

    protected bool $shared = true;

    protected array $aliases = [AuthManager::class];
}
