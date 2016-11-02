<?php

namespace Luxury\Providers\Cli;

use Luxury\Constants\Services;
use Luxury\Providers\Provider;

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
     * @return \Phalcon\Cli\Router
     */
    protected function register()
    {
        $router = new \Phalcon\Cli\Router(true);

        $router->setDefaultTask('list');

        $router->add('list', [
            'task' => 'list'
        ]);

        $router->add('optimize', [
            'task' => 'optimize'
        ]);

        return $router;
    }
}
