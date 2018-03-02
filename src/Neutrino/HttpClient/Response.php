<?php

namespace Neutrino\HttpClient;

use Neutrino\HttpClient\Contract\Parser\Parserize;

/**
 * Class Response
 */
class Response
{
    /** @var int|null */
    public $code;

    /** @var Header */
    public $header;

    /** @var string */
    public $body = '';

    /** @var mixed */
    public $data;

    /** @var int|null */
    public $errorCode;

    /** @var string|null */
    public $error;

    /** @var mixed */
    public $providerDatas;

    /**
     * Response constructor.
     *
     * @param \Neutrino\HttpClient\Header $header
     */
    public function __construct(Header $header = null)
    {
        $this->header = $header === null ? new Header() : $header;
    }

    /**
     * Check if the HTTP Response is valid (2xx)
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->code >= 200 && $this->code < 300;
    }

    /**
     * Check if an HTTP error append
     *
     * @return bool
     */
    public function isFail()
    {
        return $this->code < 200 || $this->code >= 300;
    }

    /**
     * Check if an CURL error append
     *
     * @return bool
     */
    public function isError()
    {
        return isset($this->errorCode) && ($this->errorCode !== null || $this->errorCode !== 0);
    }

    /**
     * @param Parserize|string $parser
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function parse($parser)
    {
        if (is_string($parser)) {
            $parser = new $parser;
        }

        if ($parser instanceof Parserize) {
            $this->data = $parser->parse($this->body);

            return $this;
        }

        throw new \RuntimeException(__METHOD__ . ': $parserize must implement ' . Parserize::class);
    }
}
