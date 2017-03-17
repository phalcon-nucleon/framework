<?php

namespace Neutrino\Providers\Micro;

use Neutrino\Constants\Services;
use Neutrino\Providers\Provider;

class Router extends Provider
{
    protected $name = Services::ROUTER;

    protected $shared = true;

    /**
     * Return the service to register
     *
     * Called when the services container tries to resolve the service
     *
     * @return mixed
     */
    protected function register()
    {
        return $this->{Services::APP};
    }
}