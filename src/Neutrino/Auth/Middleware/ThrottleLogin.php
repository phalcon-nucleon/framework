<?php

namespace Neutrino\Auth\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Middleware\Throttle as ThrottleMiddleware;

/**
 * Class Throttle
 *
 *  @package Neutrino\Auth\Middleware
 */
class ThrottleLogin extends ThrottleMiddleware
{
    protected $name = Services::AUTH;
}
