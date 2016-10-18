<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events\Application as AppEvent;
use Luxury\Middleware\AfterMiddleware;
use Luxury\Middleware\BeforeMiddleware;
use Luxury\Middleware\FinishMiddleware;
use Luxury\Middleware\InitMiddleware;
use Luxury\Middleware\Middleware;

/**
 * ApplicationMiddleware
 *
 * Class Application
 *
 * @package Luxury\Foundation\Middleware
 */
abstract class Application extends Middleware
{
    /**
     * ApplicationMiddleware constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if ($this instanceof InitMiddleware) {
            $this->listen[AppEvent::BOOT] = 'init';
        }
        if ($this instanceof BeforeMiddleware) {
            $this->listen[AppEvent::BEFORE_HANDLE_REQUEST] = 'before';
        }
        if ($this instanceof AfterMiddleware) {
            $this->listen[AppEvent::AFTER_HANDLE_REQUEST] = 'after';
        }
        if ($this instanceof FinishMiddleware) {
            $this->listen[AppEvent::BEFORE_SEND_RESPONSE] = 'finish';
        }
    }
}
