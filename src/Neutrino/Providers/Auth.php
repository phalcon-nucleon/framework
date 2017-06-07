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
    protected $class = AuthManager::class;

    protected $name = Services::AUTH;

    protected $shared = true;

    protected $aliases = [AuthManager::class];
}
