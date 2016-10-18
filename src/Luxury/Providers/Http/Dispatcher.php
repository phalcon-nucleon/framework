<?php

namespace Luxury\Providers\Http;

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
