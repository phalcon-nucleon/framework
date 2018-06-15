<?php

namespace Neutrino\HttpClient\Contract\Streaming;

use Phalcon\Events\EventsAwareInterface;

/**
 * Interface Streamable
 *
 * @package Neutrino\HttpClient\Contract\Streaming
 */
interface Streamable extends EventsAwareInterface
{
    const EVENT_START    = 'stream:start';
    const EVENT_PROGRESS = 'stream:progress';
    const EVENT_FINISH   = 'stream:finish';

    /**
     * @param string $event
     * @param \Closure $callback
     *
     * @return $this
     */
    public function on($event, $callback);

    /**
     * @param string $event
     * @param \Closure $callback
     *
     * @return $this
     */
    public function off($event, $callback);

    /**
     * @param int $size
     *
     * @return $this
     */
    public function setBufferSize($size);
}
