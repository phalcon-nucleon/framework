<?php

namespace Bench\Kernels;

use Neutrino\Foundation\Http\Kernel;
use Neutrino\Providers;

class HttpKernel extends Kernel
{
    protected $providers = [
        Providers\Url::class,
        Providers\Http\Router::class,
        Providers\Http\Dispatcher::class,
    ];
}
