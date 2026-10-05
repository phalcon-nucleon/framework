<?php

declare(strict_types=1);

namespace Neutrino\Providers\Micro;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;

/**
 * Class Router
 *
 * @package Neutrino\Providers\Micro
 */
class Router extends SimpleProvider
{
    protected string $class = \Neutrino\Micro\Router::class;

    protected string $name = Services::MICRO_ROUTER;

    protected bool $shared = true;
}
