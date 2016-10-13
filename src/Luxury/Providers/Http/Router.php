<?php

namespace Luxury\Providers\Http;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Luxury\Providers\Provider;
use Phalcon\DiInterface;

/**
 * Class Router
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Router extends Provider
{
    protected $name = Services::ROUTER;

    protected $shared = true;

    /**
     * @param \Phalcon\DiInterface $di
     */
    protected function register(DiInterface $di)
    {
        //Registering the Router
        //$di->setShared(Services::ROUTER, function () {
            $router = new \Phalcon\Mvc\Router(false);

            $router->setUriSource(\Phalcon\Mvc\Router::URI_SOURCE_SERVER_REQUEST_URI);

            return $router;
        //});
    }
}
