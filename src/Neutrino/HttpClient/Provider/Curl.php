<?php

namespace Neutrino\HttpClient\Provider;

use Neutrino\Http\Standards\Method;
use Neutrino\HttpClient\Exception as HttpException;
use Neutrino\HttpClient\Provider\Exception as ProviderException;
use Neutrino\HttpClient\Request;

/**
 * Class Curl
 *
 * @package Neutrino\HttpClient\Provider
 */
class Curl extends Request
{
    private static $isAvailable;

    public static function checkAvailability()
    {
        if (!isset(self::$isAvailable)) {
            self::$isAvailable = extension_loaded('curl');
        }

        if (!self::$isAvailable) {
            throw new ProviderException(static::class . ' require curl extension.');
        }
    }

    /**
     * Curl constructor.
     */
    public function __construct()
    {
        self::checkAvailability();

        parent::__construct();
    }

    /**
     * Define request timeout
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        return $this->setOption(CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * Define connection timeout
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setConnectTimeout($timeout)
    {
        return $this->setOption(CURLOPT_CONNECTTIMEOUT, $timeout);
    }

    /**
     * Disable ssl verify
     *
     * @return $this
     */
    public function disableSsl()
    {
        return $this
            ->setOption(CURLOPT_SSL_VERIFYHOST, false)
            ->setOption(CURLOPT_SSL_VERIFYPEER, false);
    }

    /**
     * @return \Neutrino\HttpClient\Response
     * @throws \Exception
     */
    protected function makeCall()
    {
        try {
            $ch = curl_init();

            $this->curlOptions($ch);

            $this->curlExec($ch);

            $this->curlInfos($ch);

            if ($this->response->errorCode) {
                throw new HttpException($this->response->error, $this->response->errorCode);
            }

            return $this->response;
        } finally {
            if (isset($ch) && is_resource($ch)) {
                curl_close($ch);
            }
        }
    }

    /**
     * Apply options to curl resource
     *
     * @param resource $ch
     */
    protected function curlOptions($ch)
    {
        $method = $this->method;

        if ($method === Method::HEAD) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        // Default Options
        curl_setopt_array($ch,
            [
                CURLOPT_URL             => $this->uri->build(),
                CURLOPT_CUSTOMREQUEST   => $method,
                CURLOPT_AUTOREFERER     => true,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_MAXREDIRS       => 20,
                CURLOPT_HEADER          => $this->fullResponse,
                CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT  => 30,
                CURLOPT_TIMEOUT         => 30,
                CURLOPT_HEADERFUNCTION  => [$this, 'curlHeaderFunction'],
            ]);

        curl_setopt_array($ch, $this->options);
    }

    /**
     * @param resource $ch
     *
     * @return void
     */
    protected function curlExec($ch)
    {
        $result = curl_exec($ch);

        $this->response->body = $result;
    }

    /**
     * Callback headerFunction
     *
     * @param resource $ch
     * @param string   $raw
     *
     * @return int
     */
    protected function curlHeaderFunction($ch, $raw)
    {
        if ($this->response->code === null) {
            $this->response->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        $this->response->header->parse($raw);

        return strlen($raw);
    }

    /**
     * Apply curl info to response
     *
     * @param resource $ch
     */
    protected function curlInfos($ch)
    {
        if ($this->response->code === null) {
            $this->response->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        if (($errno = curl_errno($ch)) !== 0) {
            $this->response->errorCode = curl_errno($ch);
            $this->response->error     = curl_error($ch);
        }

        $this->response->providerDatas = curl_getinfo($ch);
    }

    /**
     * Build request parameters
     *
     * @return $this
     */
    protected function buildParams()
    {
        if ($this->isPostMethod()) {
            if ($this->isJsonRequest()) {
                return $this
                    ->setOption(CURLOPT_POSTFIELDS, json_encode($this->params))
                    ->setHeader('Content-Type', 'application/json');
            }

            return $this
                ->setOption(CURLOPT_POSTFIELDS, http_build_query($this->params))
                ->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        }

        return $this->buildUrl();
    }

    /**
     * Build Cookies
     *
     * @return $this
     */
    protected function buildCookies()
    {
        if (!empty($this->cookies)) {
            return $this->setOption(CURLOPT_COOKIE, $this->getCookies(true));
        }

        return $this;
    }

    /**
     * Build headers
     *
     * @return $this
     */
    protected function buildHeaders()
    {
        if (!empty($this->header->getHeaders())) {
            return $this->setOption(CURLOPT_HTTPHEADER, $this->header->build());
        }

        return $this;
    }

    /**
     * Build proxy
     *
     * @return $this
     */
    protected function buildProxy()
    {
        if (isset($this->proxy['host'])) {
            $this
                ->setOption(CURLOPT_PROXY, $this->proxy['host'])
                ->setOption(CURLOPT_PROXYPORT, $this->proxy['port']);

            if (isset($this->proxy['access'])) {
                $this->setOption(CURLOPT_PROXYUSERPWD, $this->proxy['access']);
            }
        }

        return $this;
    }
}
