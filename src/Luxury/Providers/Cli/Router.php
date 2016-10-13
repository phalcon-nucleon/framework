<?php

namespace Luxury\Providers\Cli;

use Luxury\Constants\Services;
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
            $router = new \Phalcon\Cli\Router(false);

            return $router;
        //});
    }
}
