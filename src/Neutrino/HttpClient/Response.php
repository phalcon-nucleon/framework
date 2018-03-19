<?php

namespace Neutrino\HttpClient;

use Neutrino\HttpClient\Contract\Parser\Parserize;

/**
 * Class Response
 */
class Response
{
    /** @var int|null */
    protected $code;

    /** @var Header */
    protected $header;

    /** @var string */
    protected $body = '';

    /** @var mixed */
    protected $data;

    /** @var int|null */
    protected $errorCode;

    /** @var string|null */
    protected $error;

    /** @var mixed */
    protected $providerDatas;

    /**
     * Response constructor.
     *
     * @param \Neutrino\HttpClient\Header $header
     */
    public function __construct(Header $header = null)
    {
        $this->setHeader($header === null ? new Header() : $header);
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
     * Check if an HTTP Response is a redirection (3xx)
     *
     * @return bool
     */
    public function isRedirect()
    {
        return $this->code >= 300 && $this->code < 400;
    }


    /**
     * Check if an HTTP error append (4xx, 5xx)
     *
     * @return bool
     */
    public function isFail()
    {
        return $this->code >= 400;
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

    /**
     * @return int|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int|null $code
     *
     * @return Response
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return \Neutrino\HttpClient\Header
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param \Neutrino\HttpClient\Header $header
     *
     * @return Response
     */
    public function setHeader(Header $header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return Response
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return null|string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param null|string $error
     *
     * @return Response
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int|null $errorCode
     *
     * @return Response
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProviderDatas()
    {
        return $this->providerDatas;
    }

    /**
     * @param mixed $providerDatas
     *
     * @return Response
     */
    public function setProviderDatas($providerDatas)
    {
        $this->providerDatas = $providerDatas;

        return $this;
    }
}
