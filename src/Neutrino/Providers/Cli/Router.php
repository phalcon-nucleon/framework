<?php

namespace Neutrino\Providers\Cli;

use Neutrino\Constants\Services;
use Neutrino\Cli\Router as NeutrinoRouter;
use Neutrino\Database\Cli\Tasks\FreshTask;
use Neutrino\Database\Cli\Tasks\InstallTask;
use Neutrino\Database\Cli\Tasks\MakerTask;
use Neutrino\Database\Cli\Tasks\MigrateTask;
use Neutrino\Database\Cli\Tasks\RefreshTask;
use Neutrino\Database\Cli\Tasks\ResetTask;
use Neutrino\Database\Cli\Tasks\RollbackTask;
use Neutrino\Database\Cli\Tasks\StatusTask;
use Neutrino\Foundation\Cli\Tasks\ClearCompiledTask;
use Neutrino\Foundation\Cli\Tasks\ConfigCacheTask;
use Neutrino\Foundation\Cli\Tasks\ConfigClearTask;
use Neutrino\Foundation\Cli\Tasks\DefaultTask;
use Neutrino\Foundation\Cli\Tasks\HelperTask;
use Neutrino\Foundation\Cli\Tasks\ListTask;
use Neutrino\Foundation\Cli\Tasks\OptimizeTask;
use Neutrino\Foundation\Cli\Tasks\RouteListTask;
use Neutrino\Foundation\Cli\Tasks\ViewClearTask;
use Neutrino\Support\Provider;

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
     * @return \Phalcon\Cli\Router
     */
    protected function register()
    {
        $router = new NeutrinoRouter(false);

        $router->setDefaultTask(DefaultTask::class);

        $router->addTask('help ( .*)*', HelperTask::class);
        $router->addTask('list', ListTask::class);

        $router->addTask('optimize', OptimizeTask::class);
        $router->addTask('clear-compiled', ClearCompiledTask::class);

        $router->addTask('config:cache', ConfigCacheTask::class);
        $router->addTask('config:clear', ConfigClearTask::class);

        $router->addTask('route:list', RouteListTask::class);

        $router->addTask('view:clear', ViewClearTask::class);

        $router->addTask('migrate', MigrateTask::class);
        $router->addTask('migrate:install', InstallTask::class);
        $router->addTask('migrate:status', StatusTask::class);
        $router->addTask('migrate:fresh', FreshTask::class);
        $router->addTask('migrate:refresh', RefreshTask::class);
        $router->addTask('migrate:reset', ResetTask::class);
        $router->addTask('migrate:rollback', RollbackTask::class);
        $router->addTask('make:migration {name}', MakerTask::class);

        return $router;
    }
}
