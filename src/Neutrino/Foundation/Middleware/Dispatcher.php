<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Middleware;

use Neutrino\Constants\Events\Dispatch;
use Neutrino\Events\Listener;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Interfaces\Middleware\FinishInterface;
use Neutrino\Interfaces\Middleware\InitInterface;

/**
 * Middleware of the dispatcher: `init` / `finish` around the dispatch loop, `before` / `after` around each dispatch
 * (forwards included).
 */
abstract class Dispatcher extends Listener
{
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
