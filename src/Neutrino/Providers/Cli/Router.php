<?php

declare(strict_types=1);

namespace Neutrino\Providers\Cli;

use Neutrino\Cli\ProvidesTasks;
use Neutrino\Cli\Router as CliRouter;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Cli\Tasks;
use Neutrino\Support\Provider;

/**
 * Console router: the framework commands, and those of the providers implementing {@see ProvidesTasks}.
 */
class Router extends Provider implements ProvidesTasks
{
    protected string $name = Services::ROUTER;

    protected bool $shared = true;

    public static function tasks(): array
    {
        return [
            'help( .*)*'     => Tasks\HelperTask::class,
            'list'           => Tasks\ListTask::class,
            'optimize'       => Tasks\OptimizeTask::class,
            'clear-compiled' => Tasks\ClearCompiledTask::class,
            'config:cache'   => Tasks\ConfigCacheTask::class,
            'config:clear'   => Tasks\ConfigClearTask::class,
            'dotconst:cache' => Tasks\DotconstCacheTask::class,
            'route:list'     => Tasks\RouteListTask::class,
            'route:cache'    => Tasks\RouteCacheTask::class,
            'view:clear'     => Tasks\ViewClearTask::class,
            'view:cache'     => Tasks\ViewCacheTask::class,
            'server:run'     => Tasks\ServerTask::class,
            'ide-helper'     => Tasks\IdeHelperTask::class,
        ];
    }

    protected function register(): CliRouter
    {
        $router = new CliRouter(false);

        $router->setDefaultTask(Tasks\DefaultTask::class);

        $application = $this->getDI()->getShared(Services::APP);
        $providers = is_object($application) && method_exists($application, 'getProviders') ? $application->getProviders() : [];
        $providers = is_array($providers) ? array_filter($providers, 'is_string') : [];

        foreach (array_unique([static::class, ...array_values($providers)]) as $provider) {
            if (!is_subclass_of($provider, ProvidesTasks::class)) {
                continue;
            }

            foreach ($provider::tasks() as $command => $task) {
                [$class, $action] = is_array($task) ? [$task[0], $task[1] ?? null] : [$task, null];

                $router->addTask($command, $class, $action);
            }
        }

        return $router;
    }
}
