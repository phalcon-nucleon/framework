<?php

namespace Luxury\Providers;

use Luxury\Auth\AuthManager;
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
     * @return \Luxury\Auth\AuthManager
     */
    protected function register()
    {
        return new AuthManager();
    }
}
