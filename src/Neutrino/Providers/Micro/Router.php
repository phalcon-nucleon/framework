<?php

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
    protected $class = \Neutrino\Micro\Router::class;

    protected $name = Services::MICRO_ROUTER;

    protected $shared = true;
}