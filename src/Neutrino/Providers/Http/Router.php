<?php

namespace Neutrino\Providers\Http;

use Neutrino\Constants\Services;
use Neutrino\Providers\Provider;


/**
 * Class Router
 *
 *  @package Neutrino\Foundation\Bootstrap
 */
class Router extends Provider
{
    protected $name = Services::ROUTER;

    protected $shared = true;

    /**
     * @return \Phalcon\Mvc\Router
     */
    protected function register()
    {
        $router = new \Phalcon\Mvc\Router(false);

        $router->setUriSource(\Phalcon\Mvc\Router::URI_SOURCE_SERVER_REQUEST_URI);

        return $router;
    }
}
