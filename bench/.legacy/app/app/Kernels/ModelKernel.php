<?php

namespace Bench\Kernels;

use Neutrino\Providers;

class ModelKernel extends HttpKernel
{
    protected $providers = [
        Providers\Url::class,
        Providers\Http\Router::class,
        Providers\Http\Dispatcher::class,
        Providers\Database::class,
        Providers\Model::class,
    ];
}
