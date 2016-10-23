<?php

namespace Test\Stub;

use Luxury\Foundation\Application\Http as HttpApplication;
use Phalcon\Di;

/**
 * Class StubKernelEmpty
 *
 * @package     Test\Stub
 */
class StubKernelEmpty extends HttpApplication
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
