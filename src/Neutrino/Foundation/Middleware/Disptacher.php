<?php

namespace Neutrino\Foundation\Middleware;

use Neutrino\Constants\Events\Dispatch;
use Neutrino\Events\Listener;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Interfaces\Middleware\FinishInterface;
use Neutrino\Interfaces\Middleware\InitInterface;

/**
 * Class DisptacherMiddleware
 *
 *  @package Neutrino\Foundation\Middleware
 */
abstract class Disptacher extends Listener
{
    /**
     * DisptacherMiddleware constructor.
     */
    public function __construct()
    {
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
