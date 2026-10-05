<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Middleware;

use Neutrino\Constants\Events\Http\Application as AppEvent;
use Neutrino\Events\Listener;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Interfaces\Middleware\FinishInterface;
use Neutrino\Interfaces\Middleware\InitInterface;

/**
 * Middleware of the HTTP application: `init` on boot, `before` / `after` around the request handling,
 * `finish` before the response is sent.
 */
abstract class Application extends Listener
{
    public function __construct()
    {
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
