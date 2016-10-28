<?php

namespace Luxury\Foundation\Middleware;

use Luxury\Constants\Events\Dispatch;
use Luxury\Events\Listener;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Interfaces\Middleware\FinishInterface;
use Luxury\Interfaces\Middleware\InitInterface;

/**
 * Class DisptacherMiddleware
 *
 * @package Luxury\Foundation\Middleware
 */
abstract class Disptacher extends Listener
{
    /**
     * DisptacherMiddleware constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if ($this instanceof InitInterface) {
            $this->listen[Dispatch::BEFORE_DISPATCH_LOOP] = 'init';
        }
        if ($this instanceof BeforeInterface) {
            $this->listen[Dispatch::BEFORE_DISPATCH] = 'before';
        }
        if ($this instanceof AfterInterface) {
            $this->listen[Dispatch::AFTER_DISPATCH] = 'after';
        }
        if ($this instanceof FinishInterface) {
            $this->listen[Dispatch::AFTER_DISPATCH_LOOP] = 'finish';
        }
    }
}
