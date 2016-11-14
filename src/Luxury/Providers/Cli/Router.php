<?php

namespace Luxury\Providers\Cli;

use Luxury\Constants\Services;
use Luxury\Cli\Router as LuxuryRouter;
use Luxury\Foundation\Cli\Tasks\ClearCompiledTask;
use Luxury\Foundation\Cli\Tasks\HelperTask;
use Luxury\Foundation\Cli\Tasks\ListTask;
use Luxury\Foundation\Cli\Tasks\OptimizeTask;
use Luxury\Foundation\Cli\Tasks\RouteListTask;
use Luxury\Foundation\Cli\Tasks\ViewClearTask;
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
        $router = new LuxuryRouter(false);

        $router->setDefaultTask(ListTask::class);

        $router->addTask('help ( .*)*', HelperTask::class);
        $router->addTask('list', ListTask::class);
        $router->addTask('optimize', OptimizeTask::class);
        $router->addTask('clear-compiled', ClearCompiledTask::class);
        $router->addTask('route:list', RouteListTask::class);
        $router->addTask('view:clear', ViewClearTask::class);

        return $router;
    }
}
