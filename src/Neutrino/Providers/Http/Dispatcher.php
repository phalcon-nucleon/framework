<?php

namespace Neutrino\Providers\Http;

use Neutrino\Constants\Services;
use Neutrino\Providers\Provider;

/**
 * Class Dispatcher
 *
 * @package Neutrino\Providers
 */
class Dispatcher extends Provider
{
    protected $name = Services::DISPATCHER;

    protected $shared = true;

    protected $aliases = [\Phalcon\Mvc\Dispatcher::class];

    /**
     * @return \Phalcon\Mvc\Dispatcher
     */
    protected function register()
    {
        $dispatcher = new \Phalcon\Mvc\Dispatcher();

        // Assign the events manager to the dispatcher
        $dispatcher->setEventsManager($this->{Services::EVENTS_MANAGER});

        return $dispatcher;
    }
}
