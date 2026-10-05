<?php

namespace Bench\Kernels;

use Neutrino\Foundation\Micro\Kernel;
use Neutrino\Providers;

class MicroKernel extends Kernel
{
    protected $providers = [
        Providers\Url::class,
        Providers\Micro\Router::class,
    ];
}
