<?php

namespace Luxury\Auth\Middleware;

use Luxury\Middleware\Throttle as ThrottleMiddleware;

/**
 * Class Throttle
 *
 * @package Luxury\Auth\Middleware
 */
class Throttle extends ThrottleMiddleware
{
    protected $name = 'auth';
}
