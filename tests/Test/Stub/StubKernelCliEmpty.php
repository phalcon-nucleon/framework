<?php

namespace Test\Stub;

use Luxury\Foundation\Kernel\Cli as CliApplication;
use Phalcon\Di;

/**
 * Class StubKernelEmpty
 *
 * @package     Test\Stub
 */
class StubKernelCliEmpty extends CliApplication
{

    protected $dependencyInjection = Di::class;

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
