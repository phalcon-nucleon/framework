<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;

/**
 * Class Crypt
 *
 *  @package Neutrino\Providers
 */
class Crypt extends Provider
{
    protected $class = \Phalcon\Crypt::class;

    protected $name = Services::CRYPT;

    protected $shared = true;

    protected $aliases = [\Phalcon\Crypt::class];

    /**
     * Return the service to register
     *
     * Called when the services container tries to resolve the service
     *
     * @return mixed
     */
    protected function register()
    {
        $app = $this->config->app;

        $crypt = new \Phalcon\Crypt($app->cipher);

        $crypt->setKey($app->key);

        return $crypt;
    }
}
