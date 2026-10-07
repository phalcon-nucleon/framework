<?php

namespace RectorFixture;

use Neutrino\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $providers = [];

    protected $middlewares = [];

    protected $dependencyInjection;

    public function registerRoutes()
    {
    }
}
