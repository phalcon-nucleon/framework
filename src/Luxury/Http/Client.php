<?php

namespace Luxury\Http;

use Phalcon\Http\Client\Request;
use Phalcon\Http\Client\Response;
use Phalcon\Http\Request\Method;
use Phalcon\Http\Response\StatusCode;

/**
 * Class Client
 *
 * @package     Luxury\Http
 */
class Client
{
    /**
     * @var \Phalcon\Http\Client\Request
     */
    private $provider;

    /**
     * Client constructor.
     *
     * @param null $provider
     *
     * @throws \Phalcon\Http\Client\Provider\Exception
     */
    public function __construct($provider = null)
    {
        $this->provider = $provider ? $provider : Request::getProvider();
    }

    /**
     * HTTP GET Method
     *
     * @param string        $url
     * @param array         $params
     * @param array         $header
     * @param callable|bool $autoRedirectCallback
     *
     * @return \Phalcon\Http\Client\Response
     * @throws \Exception
     * @throws \Phalcon\Http\Client\Provider\Exception
     */
    public function get(
        $url,
        array $params = [],
        array $header = [],
        $autoRedirectCallback = false
    ) {
        return self::call(Method::GET, $url, $params, $header, $autoRedirectCallback);
    }

    /**
     * HTTP POST Method
     *
     * @param string        $url
     * @param array         $params
     * @param array         $header
     * @param callable|bool $autoRedirectCallback
     *
     * @return \Phalcon\Http\Client\Response
     * @throws \Exception
     * @throws \Phalcon\Http\Client\Provider\Exception
     */
    public function post(
        $url,
        array $params = [],
        array $header = [],
        $autoRedirectCallback = false
    ) {
        return self::call(Method::POST, $url, $params, $header, $autoRedirectCallback);
    }

    /**
     * HTTP PUT Method
     *
     * @param string        $url
     * @param array         $params
     * @param array         $header
     * @param callable|bool $autoRedirectCallback
     *
     * @return \Phalcon\Http\Client\Response
     * @throws \Exception
     * @throws \Phalcon\Http\Client\Provider\Exception
     */
    public function put(
        $url,
        array $params = [],
        array $header = [],
        $autoRedirectCallback = false
    ) {
        return self::call(Method::PUT, $url, $params, $header, $autoRedirectCallback);
    }

    /**
     * HTTP DELETE Method
     *
     * @param string        $url
     * @param array         $params
     * @param array         $header
     * @param callable|bool $autoRedirectCallback
     *
     * @return \Phalcon\Http\Client\Response
     * @throws \Exception
     * @throws \Phalcon\Http\Client\Provider\Exception
     */
    public function delete(
        $url,
        array $params = [],
        array $header = [],
        $autoRedirectCallback = false
    ) {
        return self::call(Method::DELETE, $url, $params, $header, $autoRedirectCallback);
    }

    /**
     * HTTP PATCH Method
     *
     * @param string        $url
     * @param array         $params
     * @param array         $header
     * @param callable|bool $autoRedirectCallback
     *
     * @return \Phalcon\Http\Client\Response
     * @throws \Exception
     * @throws \Phalcon\Http\Client\Provider\Exception
     */
    public function patch(
        $url,
        array $params = [],
        array $header = [],
        $autoRedirectCallback = false
    ) {
        return self::call(Method::PATCH, $url, $params, $header, $autoRedirectCallback);
    }

    /**
     * Make an HTTP Request
     *
     * @param string        $method
     * @param string        $url
     * @param array         $params
     * @param array         $header
     * @param callable|bool $autoRedirect
     *
     * @return \Phalcon\Http\Client\Response
     * @throws \Exception
     * @throws \Phalcon\Http\Client\Provider\Exception
     */
    private function call(
        $method,
        $url,
        array $params = [],
        array $header = [],
        $autoRedirect = null
    ) {

        switch (strtoupper($method)) {
            case Method::GET:
                $response = $this->provider->get($url, $params, $header);
                break;
            case Method::POST:
                $response = $this->provider->post($url, $params, true, $header);
                break;
            case Method::PUT:
                $response = $this->provider->put($url, $params, true, $header);
                break;
            case Method::DELETE:
                $response = $this->provider->delete($url, $params, $header);
                break;
            case Method::PATCH:
                $response = $this->provider->patch($url, $params, $header);
                break;
            default:
                throw new \BadMethodCallException('Http Method "' . strval($method) . '" not implemented.');
        }

        $statusCode = $response->header->statusCode;

        if (!$this->isOk($response)) {
            if ($this->isRedirect($response)) {
                switch ($statusCode) {
                    case StatusCode::MOVED_PERMANENTLY:
                    case StatusCode::FOUND:
                    case StatusCode::TEMPORARY_REDIRECT:
                    case StatusCode::PERMANENT_REDIRECT:
                        if (!empty($autoRedirect) && $response->header->has('Location')) {
                            $uri = $response->header->get('Location');
                            if (is_callable($autoRedirect)) {
                                $uri = $autoRedirect($uri);
                            }

                            return self::call($method, $uri, $params, $header, $autoRedirect);
                        }
                        break;
                }
            }
        }

        return $response;
    }

    /**
     * Return true if a response code is between 100 & 300
     *
     * @param \Phalcon\Http\Client\Response $response
     *
     * @return bool
     */
    public function isOk(Response $response)
    {
        $code = $response->header->statusCode;

        return $code >= StatusCode::CONTINUES && $code < StatusCode::MULTIPLE_CHOICES;
    }

    /**
     * Return true if a response code is between 200 & 300
     *
     * @param \Phalcon\Http\Client\Response $response
     *
     * @return bool
     */
    public function isSuccess(Response $response)
    {
        $code = $response->header->statusCode;

        return $code >= StatusCode::OK && $code < StatusCode::MULTIPLE_CHOICES;
    }

    /**
     * Return true if a response code is between 300 & 400
     *
     * @param \Phalcon\Http\Client\Response $response
     *
     * @return bool
     */
    public function isRedirect(Response $response)
    {
        $code = $response->header->statusCode;

        return $code >= StatusCode::MULTIPLE_CHOICES && $code < StatusCode::BAD_REQUEST;
    }

    /**
     * Return true if a response code is between 400 & 500
     *
     * @param \Phalcon\Http\Client\Response $response
     *
     * @return bool
     */
    public function isFail(Response $response)
    {
        $code = $response->header->statusCode;

        return $code >= StatusCode::BAD_REQUEST && $code < StatusCode::INTERNAL_SERVER_ERROR;
    }

    /**
     * Return true if a response code is over than 500
     *
     * @param \Phalcon\Http\Client\Response $response
     *
     * @return bool
     */
    public function isError(Response $response)
    {
        $code = $response->header->statusCode;

        return $code >= StatusCode::INTERNAL_SERVER_ERROR;
    }
}
