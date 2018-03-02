<?php

namespace Neutrino\HttpClient\Contract\Streaming;

use Phalcon\Events\Manager;

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
     * @return \Phalcon\Events\Manager
     */
    protected function getEventManager()
    {
        if (!isset($this->eventManager)) {
            $this->eventManager = new Manager();
        }

        return $this->eventManager;
    }

    /**
     * @param $event
     * @param $callback
     *
     * @return $this
     */
    public function on($event, $callback)
    {
        $this->checkEvent($event);

        $this->getEventManager()->attach($event, $callback);

        return $this;
    }

    /**
     * @param $event
     * @param $callback
     *
     * @return $this
     */
    public function off($event, $callback)
    {
        $this->checkEvent($event);

        $this->getEventManager()->detach($event, $callback);

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
