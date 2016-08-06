<?php

namespace Luxury\Providers\Cli;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Router
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Router implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        //Registering the Router
        $di->setShared(Services::ROUTER, function () {
            $router = new \Phalcon\Cli\Router(false);

            return $router;
        });
    }
}
