<?php

namespace Luxury\Providers\Cli;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Luxury\Providers\Provider;
use Phalcon\DiInterface;
use Phalcon\Security;

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
     * @param \Phalcon\DiInterface $di
     */
    protected function register(DiInterface $di)
    {
        //$di->setShared(
            //Services::DISPATCHER,
            //function () {
                /* @var \Phalcon\Di $this */
                $dispatcher = new \Phalcon\Cli\Dispatcher();

                // Create an events manager
                $eventsManager = $di->getShared(Services::EVENTS_MANAGER);

                // Assign the events manager to the dispatcher
                $dispatcher->setEventsManager($eventsManager);

                $dispatcher->setDefaultNamespace('\App\Cli\Tasks');

                return $dispatcher;
            //}
        //);
    }
}
