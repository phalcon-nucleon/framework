<?php

namespace Test\Stub;

use Luxury\Foundation\Http\Kernel as HttpApplication;
use Phalcon\Di;

/**
 * Class StubKernelEmpty
 *
 * @package     Test\Stub
 */
class StubKernelHttpEmpty extends HttpApplication
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
    }
}
