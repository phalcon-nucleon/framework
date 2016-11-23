<?php

namespace Neutrino\Http\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Middleware\Throttle as ThrottleMiddleware;

/**
 * Class Throttle
 *
 *  @package Neutrino\Http\Middleware
 */
class ThrottleRequest extends ThrottleMiddleware
{
    protected $name = Services::REQUEST;
}
