<?php

namespace Neutrino\HttpClient\Provider\Curl;

use Neutrino\HttpClient\Contract\Streaming\Streamable;
use Neutrino\HttpClient\Contract\Streaming\Streamize;
use Neutrino\HttpClient\Provider\Curl;

/**
 * Class Streaming
 *
 * @package Neutrino\HttpClient\Provider\Curl
 */
class Streaming extends Curl implements Streamable
{
    use Streamize;

    /** @var bool */
    protected $hasStarted = false;

    /**
     * Curl WRITEFUNCTION handler
     *
     * @param resource $ch
     * @param string   $content
     *
     * @return int
     */
    protected function curlWriteFunction($ch, $content)
    {
        if (!$this->hasStarted) {
            $this->hasStarted = true;

            $this->getEventManager()->fire(self::EVENT_START, $this);
        }

        $length = strlen($content);

        $this->getEventManager()->fire(self::EVENT_PROGRESS, $this, $content);

        return $length;
    }

    /**
     * Send request
     */
    public function send()
    {
        parent::send();

        $this->getEventManager()->fire(self::EVENT_FINISH, $this);
    }

    /**
     * @inheritdoc
     */
    protected function curlOptions($ch)
    {
        parent::curlOptions($ch);

        curl_setopt_array($ch,
            [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION  => [$this, 'curlWriteFunction'],
            ]);

        if (isset($this->bufferSize)) {
            curl_setopt($ch, CURLOPT_BUFFERSIZE, $this->bufferSize);
        }
    }
}
