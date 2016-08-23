<?php

namespace Luxury\Providers;

use Luxury\Auth\Manager as AuthManager;
use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Auth
 *
 * @package     Luxury\Providers
 */
class Auth implements Providable
{

    /**
     * @param \Phalcon\DiInterface $di
     *
     * @return void
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::AUTH, function () {
            return new AuthManager();
        });
    }
}
