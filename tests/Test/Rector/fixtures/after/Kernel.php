<?php

namespace RectorFixture;

use Neutrino\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected array $providers = [];

    protected array $middlewares = [];

    protected ?string $dependencyInjection = null;

    public function registerRoutes(): void
    {
    }
}
