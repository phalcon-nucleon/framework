<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events\Http\Application as AppEvent;
use Luxury\Events\Listener;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Interfaces\Middleware\FinishInterface;
use Luxury\Interfaces\Middleware\InitInterface;

/**
 * ApplicationMiddleware
 *
 * Class Application
 *
 * @package Luxury\Foundation\Middleware
 */
abstract class Application extends Listener
{
    /**
     * ApplicationMiddleware constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if ($this instanceof InitInterface) {
            $this->listen[AppEvent::BOOT] = 'init';
        }
        if ($this instanceof BeforeInterface) {
            $this->listen[AppEvent::BEFORE_HANDLE] = 'before';
        }
        if ($this instanceof AfterInterface) {
            $this->listen[AppEvent::AFTER_HANDLE] = 'after';
        }
        if ($this instanceof FinishInterface) {
            $this->listen[AppEvent::BEFORE_SEND_RESPONSE] = 'finish';
        }
    }
}
