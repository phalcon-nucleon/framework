<?php

namespace Luxury\Providers\Cli;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;
use Phalcon\Security;

/**
 * Class Dispatcher
 *
 * @package Luxury\Bootstrap\Services
 */
class Dispatcher implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(
            Services::DISPATCHER,
            function () {
                /* @var \Phalcon\Di $this */
                $dispatcher = new \Phalcon\Cli\Dispatcher();

                // Create an events manager
                $eventsManager = $this->getShared(Services::EVENTS_MANAGER);

                // Listen for events produced in the dispatcher using the Security plugin
                /*$eventsManager->attach(
                    'dispatch:beforeExecuteRoute',
                    $this->getShared(Services::SECURITY)
                );*/

                // Assign the events manager to the dispatcher
                $dispatcher->setEventsManager($eventsManager);

                $dispatcher->setDefaultNamespace('\App\Cli\Tasks');

                return $dispatcher;
            }
        );
    }
}
