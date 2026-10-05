<?php

namespace Neutrino\Providers\Http;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;

/**
 * Class Dispatcher
 *
 * @package Neutrino\Providers
 */
class Dispatcher extends Provider
{
    protected string $name = Services::DISPATCHER;

    protected bool $shared = true;

    protected array $aliases = [\Phalcon\Mvc\Dispatcher::class];

    /**
     * @return \Phalcon\Mvc\Dispatcher
     */
    protected function register()
    {
        $dispatcher = new \Phalcon\Mvc\Dispatcher();

        // Assign the events manager to the dispatcher
        $dispatcher->setEventsManager($this->getDI()->getShared(Services::EVENTS_MANAGER));

        return $dispatcher;
    }
}
