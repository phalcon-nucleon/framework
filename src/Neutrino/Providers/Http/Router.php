<?php

namespace Neutrino\Providers\Http;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;


/**
 * Class Router
 *
 * @package Neutrino\Foundation\Bootstrap
 */
class Router extends Provider
{
    protected string $name = Services::ROUTER;

    protected bool $shared = true;

    protected array $aliases = [\Phalcon\Mvc\Router::class];

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
