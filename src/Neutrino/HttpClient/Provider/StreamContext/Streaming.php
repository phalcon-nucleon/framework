<?php

namespace Neutrino\HttpClient\Provider\StreamContext;

use Neutrino\HttpClient\Contract\Streaming\Streamable;
use Neutrino\HttpClient\Contract\Streaming\Streamize;
use Neutrino\HttpClient\Provider\StreamContext;

/**
 * Class Streaming
 *
 * @package Neutrino\HttpClient\Provider\StreamContext
 */
class Streaming extends StreamContext implements Streamable
{
    use Streamize;

    /**
     * @param $context
     *
     * @return bool
     */
    protected function streamContextExec($context)
    {
        $emitter = $this->getEventManager();

        try {
            $handler = fopen($this->uri->build(), 'r', null, $context);

            $this->streamContextParseHeader($http_response_header);

            $this->response->providerDatas = stream_get_meta_data($handler);

            $emitter->fire(self::EVENT_START, $this);

            $buffer = $this->bufferSize ? $this->bufferSize : 4096;

            while (!feof($handler)) {
                $emitter->fire(self::EVENT_PROGRESS, $this, stream_get_contents($handler, $buffer));
            }

            $this->response->providerDatas = stream_get_meta_data($handler);

            $emitter->fire(self::EVENT_FINISH, $this);

            return true;
        } finally {
            if (isset($handler) && is_resource($handler)) {
                fclose($handler);
            }
        }
    }

}
