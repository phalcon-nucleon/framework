<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events\Dispatch;
use Luxury\Middleware\AfterMiddleware;
use Luxury\Middleware\BeforeMiddleware;
use Luxury\Middleware\FinishMiddleware;
use Luxury\Middleware\InitMiddleware;
use Luxury\Middleware\Middleware;

/**
 * Class DisptacherMiddleware
 *
 * @package Luxury\Middleware
 */
abstract class Disptacher extends Middleware
{
    /**
     * DisptacherMiddleware constructor.
     */
    final public function __construct()
    {
        if ($this instanceof InitMiddleware) {
            $this->listen[Dispatch::BEFORE_DISPATCH_LOOP] = 'init';
        }
        if ($this instanceof BeforeMiddleware) {
            $this->listen[Dispatch::BEFORE_DISPATCH] = 'before';
        }
        if ($this instanceof AfterMiddleware) {
            $this->listen[Dispatch::AFTER_DISPATCH] = 'after';
        }
        if ($this instanceof FinishMiddleware) {
            $this->listen[Dispatch::AFTER_DISPATCH_LOOP] = 'finish';
        }
    }
}
