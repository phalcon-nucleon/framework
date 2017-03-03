<?php

namespace Neutrino\Providers;

use Neutrino\Auth\Manager as AuthManager;
use Neutrino\Constants\Services;

/**
 * Class Auth
 *
 *  @package Neutrino\Providers
 */
class Auth extends Provider
{

    protected $name = Services::AUTH;

    protected $shared = true;

    protected $aliases = [AuthManager::class];

    /**
     * @return \Neutrino\Auth\Manager
     */
    protected function register()
    {
        return new AuthManager();
    }
}
