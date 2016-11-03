<?php

namespace Luxury\Providers\Cli;

use Luxury\Constants\Services;
use Luxury\Foundation\Cli\ClearCompiledTask;
use Luxury\Foundation\Cli\ListTask;
use Luxury\Foundation\Cli\OptimizeTask;
use Luxury\Foundation\Cli\RouteListTask;
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
        $router = new \Luxury\Cli\Router(false);

        $router->setDefaultTask($router->classToTask(ListTask::class));

        $router->addTask('list', ListTask::class);
        $router->addTask('optimize', OptimizeTask::class);
        $router->addTask('clear-compiled', ClearCompiledTask::class);
        $router->addTask('route:list', RouteListTask::class);

        return $router;
    }
}
