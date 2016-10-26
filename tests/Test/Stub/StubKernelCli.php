<?php

namespace Test\Stub;

use Luxury\Foundation\Kernel\Cli as CliApplication;

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
    protected $providers = [];

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
