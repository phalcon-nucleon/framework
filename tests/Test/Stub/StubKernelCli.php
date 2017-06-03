<?php

namespace Test\Stub;

use Neutrino\Foundation\Cli\Kernel as CliApplication;
use Neutrino\Providers\Cli\Dispatcher;
use Neutrino\Providers\Cli\Output;
use Neutrino\Providers\Cli\Router;

/**
 * Class StubKernelEmpty
 *
 * @package     Test\Stub
 */
class StubKernelCli extends CliApplication
{
    /**
     * Return the Provider List to load.
     *
     * @var string[]
     */
    protected $providers = [
        Output::class,
        Dispatcher::class,
        Router::class
    ];

    /**
     * Register the routes.
     *
     * @return void
     */
    public function registerRoutes()
    {
        // TODO: Implement registerRoutes() method.
    }
}
