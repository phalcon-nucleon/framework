<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Middleware;

use Neutrino\Constants\Events;
use Neutrino\Constants\Services;
use Neutrino\Events\Listener;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Interfaces\Middleware\FinishInterface;
use Phalcon\Dispatcher\AbstractDispatcher;
use Phalcon\Events\Event;

/**
 * Middleware of a controller: `before` / `after` around the controller action, `finish` after the dispatch.
 * Applied only to the controller that registered it, and filtered by {@see only()} / {@see except()}.
 */
abstract class Controller extends Listener
{
    /**
     * Action filters: `only` and `except`, as sets of action names.
     *
     * @var array{only?: array<string, true>, except?: array<string, true>}
     */
    private array $filter = [];

    /**
     * @param string $controllerClass The controller that registers this middleware
     */
    public function __construct(private readonly string $controllerClass)
    {
        if ($this instanceof BeforeInterface) {
            $this->listen[Events\Dispatch::BEFORE_EXECUTE_ROUTE] = 'checkBefore';
        }
        if ($this instanceof AfterInterface) {
            $this->listen[Events\Dispatch::AFTER_EXECUTE_ROUTE] = 'checkAfter';
        }
        if ($this instanceof FinishInterface) {
            $this->listen[Events\Dispatch::AFTER_DISPATCH] = 'checkFinish';
        }
    }

    /**
     * Whether the middleware applies to the action being dispatched.
     */
    final public function check(): bool
    {
        /** @var AbstractDispatcher $dispatcher */
        $dispatcher = $this->getDI()->getShared(Services::DISPATCHER);

        if ($dispatcher->wasForwarded() && !$dispatcher->isFinished()) {
            // The controller has just been forwarded
            return false;
        }

        if ($this->controllerClass !== $dispatcher->getHandlerClass()) {
            return false;
        }

        $action = $dispatcher->getActionName();

        $enable = !isset($this->filter['only']) || isset($this->filter['only'][$action]);

        return $enable && !isset($this->filter['except'][$action]);
    }

    /**
     * Applies the middleware to these actions only. Calls add up; `[]` resets the list (the middleware
     * then applies to no action), `null` changes nothing.
     *
     * @param list<string>|null $filters
     */
    final public function only(?array $filters = null): static
    {
        return $this->filters('only', $filters);
    }

    /**
     * Applies the middleware to every action but these ones. Calls add up; `[]` resets the list.
     *
     * @param list<string>|null $filters
     */
    final public function except(?array $filters = null): static
    {
        return $this->filters('except', $filters);
    }

    final public function checkBefore(Event $event, object $source, mixed $data = null): mixed
    {
        return $this instanceof BeforeInterface && $this->check() ? $this->before($event, $source, $data) : true;
    }

    final public function checkAfter(Event $event, object $source, mixed $data = null): mixed
    {
        return $this instanceof AfterInterface && $this->check() ? $this->after($event, $source, $data) : true;
    }

    final public function checkFinish(Event $event, object $source, mixed $data = null): mixed
    {
        return $this instanceof FinishInterface && $this->check() ? $this->finish($event, $source, $data) : true;
    }

    /**
     * @param 'only'|'except'   $type
     * @param list<string>|null $filters
     */
    private function filters(string $type, ?array $filters): static
    {
        if ($filters === null) {
            return $this;
        }

        $this->filter[$type] = $filters === [] ? [] : ($this->filter[$type] ?? []) + array_fill_keys($filters, true);

        return $this;
    }
}
