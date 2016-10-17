<?php

namespace Luxury\Providers\Http;

use Luxury\Constants\Services;
use Luxury\Providers\Provider;

/**
 * Class Dispatcher
 *
 * @package Luxury\Bootstrap\Services
 */
class Dispatcher extends Provider
{
    protected $name = Services::DISPATCHER;

    protected $shared = true;

    /**
     * @return \Phalcon\Mvc\Dispatcher
     */
    protected function register()
    {
        $di = $this->getDI();

        $dispatcher = new \Phalcon\Mvc\Dispatcher();

        // Create an events manager
        $eventsManager = $di->getShared(Services::EVENTS_MANAGER);

        // Listen for events produced in the dispatcher using the Security plugin
        $eventsManager->attach(
            'dispatch:beforeExecuteRoute',
            $di->getShared(Services::SECURITY)
        );

        // Assign the events manager to the dispatcher
        $dispatcher->setEventsManager($eventsManager);

        return $dispatcher;
    }
}
