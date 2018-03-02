<?php

namespace Neutrino\HttpClient\Contract\Streaming;

/**
 * Interface Streamable
 *
 * @package Neutrino\HttpClient\Contract\Streaming
 */
interface Streamable
{
    const EVENT_START    = 'start';
    const EVENT_PROGRESS = 'progress';
    const EVENT_FINISH   = 'finish';

    /**
     * @param string $event
     * @param $callback
     *
     * @return mixed
     */
    public function on($event, $callback);

    /**
     * @param $event
     * @param $callback
     *
     * @return mixed
     */
    public function off($event, $callback);

    /**
     * @param int $size
     *
     * @return mixed
     */
    public function setBufferSize($size);
}
