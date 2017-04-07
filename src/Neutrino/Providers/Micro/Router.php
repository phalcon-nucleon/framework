<?php

namespace Neutrino\Providers\Micro;

use Neutrino\Constants\Services;
use Neutrino\Providers\BasicProvider;

/**
 * Class Router
 *
 * @package Neutrino\Providers\Micro
 */
class Router extends BasicProvider
{
    protected $class = \Neutrino\Micro\Router::class;

    protected $name = Services::MICRO_ROUTER;

    protected $shared = true;

    protected $aliases = [
        \Neutrino\Micro\Router::class
    ];
}