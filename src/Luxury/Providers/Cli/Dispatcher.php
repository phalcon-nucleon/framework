<?php

namespace Luxury\Providers\Cli;

use Luxury\Constants\Services;
use Luxury\Providers\Provider;

/**
 * Class Dispatcher
 *
 * @package Luxury\Providers
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
