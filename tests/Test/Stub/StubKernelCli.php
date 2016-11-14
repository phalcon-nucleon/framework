<?php

namespace Test\Stub;

use Luxury\Foundation\Cli\Kernel as CliApplication;
use Luxury\Providers\Cli\Dispatcher;
use Luxury\Providers\Cli\Router;

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
