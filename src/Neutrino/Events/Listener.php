<?php

declare(strict_types=1);

namespace Neutrino\Events;

use Closure;
use Phalcon\Di\Injectable;
use Phalcon\Events\EventInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use RuntimeException;

/**
 * Attaches its methods to an events manager.
 */
abstract class Listener extends Injectable implements EventsAwareInterface
{
    /**
     * Events to listen, with the method to call.
     *
     * ex : [
     *      {eventName} => {methodCall},
     *      \Neutrino\Constants\Events\Kernel::BOOT => 'onBoot',
     *      \Neutrino\Constants\Events\Dispatch::BEFORE_DISPATCH => 'onBeforeDispatch'
     * ]
     *
     * @var array<array-key, string>
     */
    protected array $listen = [];

    /**
     * Event spaces to listen: the listener method named after each event of the space is called.
     *
     * ex : [
     *      \Neutrino\Constants\Events::APPLICATION,
     *      \Neutrino\Constants\Events::DISPATCH,
     * ]
     *
     * @var list<string>
     */
    protected array $space = [];

    protected ?ManagerInterface $eventsManager = null;

    /**
     * Closures attached to the events manager, by event.
     *
     * @var array<string, Closure>
     */
    private array $closures = [];

    public function getEventsManager(): ?ManagerInterface
    {
        return $this->eventsManager;
    }

    public function setEventsManager(ManagerInterface $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * Attaches the listened events and spaces to the events manager.
     */
    public function attach(): void
    {
        $em = $this->requireEventsManager();

        foreach ($this->space as $space) {
            $em->attach($space, $this);
        }

        foreach ($this->listen as $event => $callback) {
            if (!is_string($event) || !method_exists($this, $callback)) {
                throw new RuntimeException("Method '$callback' not exist in " . static::class);
            }

            $this->closures[$event] = $closure = fn(EventInterface $event, mixed $handler, mixed $data = null): mixed => $this->$callback($event, $handler, $data);

            $em->attach($event, $closure);
        }
    }

    /**
     * Detaches everything {@see Listener::attach()} attached.
     */
    public function detach(): void
    {
        $em = $this->requireEventsManager();

        foreach ($this->space as $space) {
            $em->detach($space, $this);
        }

        foreach ($this->closures as $event => $closure) {
            $em->detach($event, $closure);
        }

        $this->closures = [];
    }

    private function requireEventsManager(): ManagerInterface
    {
        return $this->eventsManager ?? throw new RuntimeException(static::class . ' has no events manager.');
    }
}
