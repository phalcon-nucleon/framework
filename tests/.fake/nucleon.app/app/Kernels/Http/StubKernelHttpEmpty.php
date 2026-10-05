<?php

namespace Fake\Kernels\Http;

use Neutrino\Foundation\Http\Kernel as HttpApplication;
use Phalcon\Di\Di;

/**
 * Class StubKernelEmpty
 *
 * @package     Test\Stub
 */
class StubKernelHttpEmpty extends HttpApplication
{

    protected ?string $dependencyInjection = Di::class;

    /**
     * Return the Provider List to load.
     *
     * @var string[]
     */
    protected array $providers = [];

    /**
     * Register the routes.
     *
     * @return void
     */
    public function registerRoutes(): void
    {
    }
}
