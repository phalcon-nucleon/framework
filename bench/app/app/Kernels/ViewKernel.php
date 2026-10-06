<?php

namespace Bench\Kernels;

use Neutrino\Providers;

class ViewKernel extends HttpKernel
{
    protected array $providers = [
        Providers\Url::class,
        Providers\Http\Router::class,
        Providers\Http\Dispatcher::class,
        Providers\View::class,
    ];
}
