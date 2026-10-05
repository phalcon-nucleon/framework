<?php

declare(strict_types=1);

namespace Neutrino\Providers\Cli;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Neutrino\Cli\Dispatcher as CliDispatcher;
use Phalcon\Events\ManagerInterface;

/**
 * Console dispatcher: tasks are full class names (no suffix), actions receive the route parameters only
 * (see {@see CliDispatcher}), attached to the shared events manager.
 */
class Dispatcher extends Provider
{
    protected string $name = Services::DISPATCHER;

    protected bool $shared = true;

    protected function register(): CliDispatcher
    {
        $dispatcher = new CliDispatcher();

        /** @var ManagerInterface $eventsManager */
        $eventsManager = $this->getDI()->getShared(Services::EVENTS_MANAGER);
        $dispatcher->setEventsManager($eventsManager);
        $dispatcher->setTaskSuffix('');

        return $dispatcher;
    }
}
