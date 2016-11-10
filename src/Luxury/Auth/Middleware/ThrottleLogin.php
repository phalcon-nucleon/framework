<?php

namespace Luxury\Auth\Middleware;

use Luxury\Constants\Services;
use Luxury\Middleware\Throttle as ThrottleMiddleware;

/**
 * Class Throttle
 *
 * @package Luxury\Auth\Middleware
 */
class ThrottleLogin extends ThrottleMiddleware
{
    protected $name = Services::AUTH;
}
