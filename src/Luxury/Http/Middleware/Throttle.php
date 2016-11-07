<?php

namespace Luxury\Http\Middleware;

use Luxury\Constants\Services;
use Luxury\Middleware\Throttle as ThrottleMiddleware;

/**
 * Class Throttle
 *
 * @package Luxury\Http\Middleware
 */
class Throttle extends ThrottleMiddleware
{
    protected $name = Services::REQUEST;
}
