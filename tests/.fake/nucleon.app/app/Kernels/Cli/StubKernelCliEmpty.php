<?php

namespace Fake\Kernels\Cli;

use Neutrino\Foundation\Cli\Kernel as CliApplication;
use Phalcon\Di\Di;

/**
 * Class StubKernelEmpty
 *
 * @package     Test\Stub
 */
class StubKernelCliEmpty extends CliApplication
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
        // TODO: Implement registerRoutes() method.
    }
}
