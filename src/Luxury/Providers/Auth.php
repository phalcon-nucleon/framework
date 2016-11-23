<?php

namespace Luxury\Providers;

use Luxury\Auth\Manager as AuthManager;
use Luxury\Constants\Services;

/**
 * Class Auth
 *
 * @package     Luxury\Providers
 */
class Auth extends Provider
{

    protected $name = Services::AUTH;

    protected $shared = true;

    /**
     * @return \Luxury\Auth\Manager
     */
    protected function register()
    {
        return new AuthManager();
    }
}
