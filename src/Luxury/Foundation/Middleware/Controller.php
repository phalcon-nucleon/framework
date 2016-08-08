<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events\Dispatch;
use Luxury\Middleware\AfterMiddleware;
use Luxury\Middleware\BeforeMiddleware;
use Luxury\Middleware\FinishMiddleware;
use Luxury\Middleware\Middleware;

/**
 * ControllerMiddleware
 *
 * Class Controller
 *
 * @package Luxury\Foundation\Middleware
 */
abstract class Controller extends Middleware
{
    /**
     * ControllerMiddleware constructor.
     */
    public function __construct()
    {
        if ($this instanceof BeforeMiddleware) {
            $this->listen[Dispatch::BEFORE_EXECUTE_ROUTE] = 'before';
        }
        if ($this instanceof AfterMiddleware) {
            $this->listen[Dispatch::AFTER_EXECUTE_ROUTE] = 'after';
        }
        if ($this instanceof FinishMiddleware) {
            $this->listen[Dispatch::AFTER_DISPATCH] = 'finish';
        }
    }
}
