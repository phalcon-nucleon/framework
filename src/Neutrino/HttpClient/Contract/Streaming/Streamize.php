<?php

namespace Neutrino\HttpClient\Contract\Streaming;

use Phalcon\Events\Manager;
use Phalcon\Events\ManagerInterface;

/**
 * Trait Streamize
 *
 * @package Neutrino\HttpClient\Contract\Streaming
 */
trait Streamize
{
    /** @var Manager */
    private $eventManager;

    /** @var int|null */
    protected $bufferSize;

    /**
     * Sets the events manager
     *
     * @param ManagerInterface $eventsManager
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        $this->eventManager = $eventsManager;
    }

    /**
     * @return \Phalcon\Events\Manager
     */
    public function getEventsManager()
    {
        if (!isset($this->eventManager)) {
            $this->setEventsManager(new Manager());
        }

        return $this->eventManager;
    }

    /**
     * @param string $event
     * @param \Closure $callback
     *
     * @return $this
     */
    public function on($event, $callback)
    {
        $this->checkEvent($event);

        $this->getEventsManager()->attach($event, $callback);

        return $this;
    }

    /**
     * @param string $event
     * @param \Closure $callback
     *
     * @return $this
     */
    public function off($event, $callback)
    {
        $this->checkEvent($event);

        $this->getEventsManager()->detach($event, $callback);

        return $this;
    }

    /**
     * @param int $size
     *
     * @return $this
     */
    public function setBufferSize($size)
    {
        $this->bufferSize = $size;

        return $this;
    }

    /**
     * @param string $event
     */
    private function checkEvent($event)
    {
        if ($event == Streamable::EVENT_START
            || $event == Streamable::EVENT_PROGRESS
            || $event == Streamable::EVENT_FINISH
        ) {
            return;
        }

        throw new \RuntimeException(static::class . ' only support ' . implode(', ',
                [
                    Streamable::EVENT_START,
                    Streamable::EVENT_PROGRESS,
                    Streamable::EVENT_FINISH,
                ]));
    }
}
