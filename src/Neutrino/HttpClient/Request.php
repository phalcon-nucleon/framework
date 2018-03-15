<?php

namespace Neutrino\HttpClient;

use Neutrino\Http\Standards\Method;

/**
 * Class Request
 *
 * @package Neutrino\HttpClient
 */
abstract class Request
{
    /*
     | Request Parameters
     */
    /** @var string */
    protected $method;

    /** @var Uri */
    protected $uri;

    /** @var array */
    protected $params = [];

    /** @var \Neutrino\HttpClient\Header */
    protected $header;

    /** @var array */
    protected $proxy = [];

    /** @var array */
    protected $cookies = [];

    /** @var array */
    protected $options = [];

    /** @var bool */
    protected $jsonRequest = false;

    /** @var bool */
    protected $fullResponse = false;

    /*
     | Response
     */
    /** @var \Neutrino\HttpClient\Response */
    protected $response;

    /**
     * Request constructor.
     *
     * @param \Neutrino\HttpClient\Header|null $header
     */
    public function __construct(Header $header = null)
    {
        $this->header = new Header();
    }

    /**
     * Return the request response's
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Return builded url
     *
     * @return Uri
     */
    public function getUri()
    {
        if ($this->isPostMethod()) {
            return $this->uri;
        }

        return $this->buildUrl()->uri;
    }

    /**
     * Define request Url
     *
     * @param string|Uri $uri
     *
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = new Uri($uri);

        return $this;
    }

    /**
     * Return request method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Define request method
     *
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Return request is based on POST (..., PUT, PATCH)
     *
     * @return bool
     */
    protected function isPostMethod()
    {
        $method = $this->getMethod();

        return $method == 'POST' || $method == 'PUT' || $method == 'PATCH';
    }

    /**
     * Return if request is a json request
     *
     * @return bool
     */
    public function isJsonRequest()
    {
        return $this->jsonRequest;
    }

    /**
     * Define if it's a json request
     *
     * @param bool $jsonRequest
     *
     * @return $this
     */
    public function setJsonRequest($jsonRequest)
    {
        $this->jsonRequest = $jsonRequest;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFullResponse()
    {
        return $this->fullResponse;
    }

    /**
     * @param bool $fullResponse
     * @return Request
     */
    public function setFullResponse($fullResponse)
    {
        $this->fullResponse = $fullResponse;

        return $this;
    }

    /**
     * Retourne les parametres de la requete
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Define the parameters
     *
     * @param array $parameters
     * @param bool $merge Merge or Erase current parameters
     *
     * @return $this
     */
    public function setParams($parameters, $merge = false)
    {
        if ($merge) {
            $this->params = array_merge($this->params, $parameters);
        } else {
            $this->params = $parameters;
        }

        return $this;
    }

    /**
     * Define a parameter
     *
     * @param string $name
     * @param string|array $value
     *
     * @return $this
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Builds the url of the request, If the HTTP method is not [POST, PUT, PATCH]
     *
     * @return $this
     */
    protected function buildUrl()
    {
        if ($this->isPostMethod()) {
            return $this;
        }

        return $this->extendUrl($this->params);
    }

    /**
     * Add parametre to the URL
     *
     * @param array $parameters
     *
     * @return $this
     */
    public function extendUrl(array $parameters = [])
    {
        if (!empty($parameters)) {
            $this->uri->extendQuery($parameters);
        }

        return $this;
    }

    /**
     * Return the headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->header->getHeaders();
    }

    /**
     * Define the headers
     *
     * @param array $headers
     * @param bool $merge Merge or Erase current headers
     *
     * @return $this
     */
    public function setHeaders($headers, $merge = false)
    {
        $this->header->setHeaders($headers, $merge);

        return $this;
    }

    /**
     * Define a header
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->header->set($name, $value);

        return $this;
    }

    /**
     * Return proxy setting
     *
     * @return array
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Define proxy setting
     *
     * @param string $host
     * @param int $port
     * @param string $access
     *
     * @return $this
     */
    public function setProxy($host, $port = 8080, $access = null)
    {
        $this->proxy = [
            'host'   => $host,
            'port'   => $port,
            'access' => $access,
        ];

        return $this;
    }

    /**
     * Return the cookies
     *
     * @param bool $format Return the cookies formatted
     *
     * @return array|string
     */
    public function getCookies($format = false)
    {
        if ($format) {
            return implode(';', $this->cookies);
        }

        return $this->cookies;
    }

    /**
     * Define the cookies
     *
     * @param array $cookies
     * @param bool $merge Merge or Erase current cookies
     *
     * @return $this
     */
    public function setCookies($cookies, $merge = false)
    {
        if ($merge) {
            $this->cookies = array_merge($this->cookies, $cookies);
        } else {
            $this->cookies = $cookies;
        }

        return $this;
    }

    /**
     * Define a cookie
     *
     * @param null|string $key
     * @param string $value
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setCookie($key, $value)
    {
        if (is_null($key)) {
            $this->cookies[] = $value;
        } else {
            $this->cookies[$key] = $value;
        }

        return $this;
    }

    /**
     * Return the options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Define the options
     *
     * @param array $options
     * @param bool $merge Est-ce que l'on ajoute les options aux options existantes, ou les ecrases
     *
     * @return $this
     */
    public function setOptions($options, $merge = false)
    {
        if ($merge) {
            $this->options = array_merge($this->options, $options);
        } else {
            $this->options = $options;
        }

        return $this;
    }

    /**
     * Define an option
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Send the request & return the response
     *
     * @return \Neutrino\HttpClient\Response
     */
    public function send()
    {
        $this
            ->buildParams()
            ->buildProxy()
            ->buildCookies()
            ->buildHeaders();

        $this->response = new Response();

        return $this->makeCall();
    }

    /**
     * Build a HTTP request
     *
     * @param string $method
     * @param string $uri
     * @param array $params
     * @param array $options
     *
     * @return $this
     */
    public function request($method, $uri, array $params = [], array $options = [])
    {
        $this
            ->setMethod($method)
            ->setUri($uri);

        if (!empty($params)) {
            $this->setParams($params, true);
        }
        if (!empty($options['headers'])) {
            $this->setHeaders($options['headers'], true);
        }
        if (isset($options['full'])) {
            $this->setFullResponse($options['full']);
        }
        if (isset($options['json'])) {
            $this->setJsonRequest($options['json']);
        }

        return $this;
    }

    /**
     * Build a HTTP [GET] request
     *
     * @param string $uri
     * @param array $params
     * @param array $options
     *
     * @return $this
     */
    public function get($uri, array $params = [], array $options = [])
    {
        return $this->request(Method::GET, $uri, $params, $options);
    }

    /**
     * Build a HTTP [HEAD] request
     *
     * @param string $uri
     * @param array $params
     * @param array $options
     *
     * @return $this
     */
    public function head($uri, array $params = [], array $options = [])
    {
        return $this->request(Method::HEAD, $uri, $params, $options);
    }

    /**
     * Build a HTTP [DELETE] request
     *
     * @param string $uri
     * @param array $params
     * @param array $options
     *
     * @return $this
     */
    public function delete($uri, array $params = [], array $options = [])
    {
        return $this->request(Method::DELETE, $uri, $params, $options);
    }

    /**
     * Build a HTTP [POST] request
     *
     * @param string $uri
     * @param array $params
     * @param array $options
     *
     * @return $this
     */
    public function post($uri, array $params = [], array $options)
    {
        return $this->request(Method::POST, $uri, $params, $options);
    }

    /**
     * Build a HTTP [PUT] request
     *
     * @param string $uri
     * @param array $params
     * @param array $options
     *
     * @return $this
     */
    public function put($uri, array $params = [], $options)
    {
        return $this->request(Method::PUT, $uri, $params, $options);
    }

    /**
     * Build a HTTP [PATCH] request
     *
     * @param string $uri
     * @param array $params
     * @param array $options
     *
     * @return $this
     */
    public function patch($uri, array $params = [], $options)
    {
        return $this->request(Method::PATCH, $uri, $params, $options);
    }

    /**
     * Define request timeout
     *
     * @param int $timeout
     *
     * @return $this
     */
    abstract public function setTimeout($timeout);

    /**
     * Disable Ssl
     *
     * @return $this
     */
    abstract public function disableSsl();

    /**
     * @return \Neutrino\HttpClient\Response
     */
    abstract protected function makeCall();

    /**
     * Build request params's
     *
     * @return $this
     */
    abstract protected function buildParams();

    /**
     * Build request header's
     *
     * @return $this
     */
    abstract protected function buildHeaders();

    /**
     * Build request proxy's
     *
     * @return $this
     */
    abstract protected function buildProxy();

    /**
     * Build request cookie's
     *
     * @return $this
     */
    abstract protected function buildCookies();
}
