<?php

namespace Bench\Kernels;

use Neutrino\Foundation\Cli\Kernel;
use Neutrino\Providers;

class CliKernel extends Kernel
{
    protected $providers = [
        Providers\Cli\Router::class,
        Providers\Cli\Dispatcher::class,
        Providers\Cli\Output::class,
    ];
}
