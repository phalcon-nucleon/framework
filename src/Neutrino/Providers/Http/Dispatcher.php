<?php

declare(strict_types=1);

namespace Neutrino\Providers\Http;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Events\ManagerInterface;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;

/**
 * MVC dispatcher, attached to the shared events manager (middlewares and listeners).
 */
class Dispatcher extends Provider
{
    protected string $name = Services::DISPATCHER;

    protected bool $shared = true;

    protected array $aliases = [MvcDispatcher::class];

    protected function register(): MvcDispatcher
    {
        $dispatcher = new MvcDispatcher();

        /** @var ManagerInterface $eventsManager */
        $eventsManager = $this->getDI()->getShared(Services::EVENTS_MANAGER);
        $dispatcher->setEventsManager($eventsManager);

        return $dispatcher;
    }
}
