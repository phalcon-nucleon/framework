<?php

namespace Luxury\Providers\Cli;

use Luxury\Constants\Services;
use Luxury\Cli\Router as LuxuryRouter;
use Luxury\Foundation\Cli\ClearCompiledTask;
use Luxury\Foundation\Cli\HelperTask;
use Luxury\Foundation\Cli\ListTask;
use Luxury\Foundation\Cli\OptimizeTask;
use Luxury\Foundation\Cli\RouteListTask;
use Luxury\Foundation\Cli\ViewClearTask;
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

        $router->setDefaultTask(LuxuryRouter::classToTask(ListTask::class));

        $router->addTask('help ( .*)*', HelperTask::class);
        $router->addTask('list', ListTask::class);
        $router->addTask('optimize', OptimizeTask::class);
        $router->addTask('clear-compiled', ClearCompiledTask::class);
        $router->addTask('route:list', RouteListTask::class);
        $router->addTask('view:clear', ViewClearTask::class);

        return $router;
    }
}
