<?php

namespace Neutrino\Providers\Cli;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;

/**
 * Class Dispatcher
 *
 *  @package Neutrino\Providers
 */
class Dispatcher extends Provider
{
    protected $name = Services::DISPATCHER;

    protected $shared = true;

    /**
     * @return \Phalcon\Cli\Dispatcher
     */
    protected function register()
    {
        $dispatcher = new \Phalcon\Cli\Dispatcher();

        // Assign the events manager to the dispatcher
        $dispatcher->setEventsManager($this->{Services::EVENTS_MANAGER});

        // Remove suffix
        $dispatcher->setTaskSuffix('');

        return $dispatcher;
    }
}
