<?php

declare(strict_types=1);

namespace Fake\Kernels\Cli;

use Neutrino\Database\Providers\MigrationsServicesProvider;
use Neutrino\Providers;
use Neutrino\Providers\Cli\Dispatcher;
use Neutrino\Providers\Cli\Output;
use Neutrino\Providers\Cli\Router;

/**
 * CLI kernel with the database and the migration commands.
 */
class StubKernelCliMigrations extends StubKernelCli
{
    protected array $providers = [
        Output::class,
        Dispatcher::class,
        Router::class,
        Providers\Database::class,
        Providers\Model::class,
        MigrationsServicesProvider::class,
    ];
}
