<?php

namespace Test\Stub;

use Neutrino\Foundation\Micro\Kernel;
use Neutrino\Providers;

class StubKernelMicro extends Kernel
{
    protected $providers = [
        Providers\Url::class,
        Providers\Http\Router::class,
        Providers\Http\Dispatcher::class,
        Providers\Cache::class,
    ];

    protected $listeners = [
        StubListener::class
    ];

    protected $middlewares = [
        //StubMiddleware::class
    ];

    public function registerRoutes()
    {
        $this->get('', function(){

        });
    }
}