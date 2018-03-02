<?php

namespace Neutrino\HttpClient;

use Neutrino\Http\Standards\Method;
use Neutrino\HttpClient\Contract\Request\Component;

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
    /**
     * Method HTTP
     *
     * @var string
     */
    protected $method;

    /**
     * Url de la requete
     *
     * @var Uri
     */
    protected $uri;

    /**
     * Parametres de la requete
     *
     * @var array
     */
    protected $params = [];

    /**
     * Header de la requete
     *
     * @var \Neutrino\HttpClient\Header
     */
    protected $header;

    /**
     * Proxy de la requete
     *
     * @var array
     */
    protected $proxy = [];

    /**
     * Authentification
     *
     * @var Component
     */
    protected $auth;

    /**
     * Cookies de la requete
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Flag spécifiant si l'on doit faire une requete JSON. Uniquement pour les methods HTTP POST, PUT, PATCH
     *
     * @var bool
     */
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
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Retour l'url de la requete, construite avec les parametres si la method HTTP n'est pas POST, PUT, PATCH
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
     * Definie l'url de la requete
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
     * Retourne la method HTTP de la requete
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Definie la method HTTP de la requete
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
     * Renvoie si l'on fais un appel HTTP basée sur le POST
     *
     * @return bool
     */
    protected function isPostMethod()
    {
        $method = $this->getMethod();

        return $method == 'POST' || $method == 'PUT' || $method == 'PATCH';
    }

    /**
     * Est-ce que l'on doit envoyer un body "json" contenant les parametres de la requete
     *
     * @return bool
     */
    public function isJsonRequest()
    {
        return $this->jsonRequest;
    }

    /**
     * Definie si l'on doit envoyer un body "json" contenant les parametres de la requete
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
     * Definie, ou ajoute, des parametres de la requete
     *
     * @param array $parameters
     * @param bool $merge Est-ce que l'on ajout les parametres aux parametres existant, ou les ecrases
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
     * Ajout un parametre à la requete
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
     * Construit l'url de la requete, Si la method HTTP n'est pas [POST, PUT, PATCH]
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
     * Ajout des parametres en GET à l'url
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
     * @return array
     */
    public function getHeaders()
    {
        return $this->header->getHeaders();
    }

    /**
     * Definie, ou ajoute, des headers à la requete
     *
     * @param array $headers
     * @param bool $merge Est-ce que l'on ajout les parametres aux parametres existant, ou les ecrases
     *
     * @return $this
     */
    public function setHeaders($headers, $merge = false)
    {
        $this->header->setHeaders($headers, $merge);

        return $this;
    }

    /**
     * Ajout un header à la requete
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
     * Retourne les informations de proxy
     *
     * @return array
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Definie les informations de proxy
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
     * Retourne les informations d'authentification
     *
     * @return Component|null
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * Definie les informations d'authentification
     *
     * @param \Neutrino\HttpClient\Contract\Request\Component $authComponent
     *
     * @return $this
     */
    public function setAuth(Component $authComponent)
    {
        $this->auth = $authComponent;

        return $this;
    }

    /**
     * Construit les informations d'authentification de la requete
     *
     * @return $this
     */
    protected function buildAuth()
    {
        if (isset($this->auth)) {
            $this->auth->build($this);
        }

        return $this;
    }

    /**
     * Retourne les cookies
     *
     * @param bool $format Retourne les cookies formatés
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
     * @param array $cookies
     * @param bool $merge Est-ce que l'on ajout les $cookies aux $cookies existant, ou les ecrases
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
     * Ajoute un cookie a la requete
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
     * Retourne les options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Definie, ou ajoute, des options
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
     * Ajout une option CURL
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @return \Neutrino\HttpClient\Response
     */
    public function send()
    {
        $this
            ->buildParams()
            ->buildAuth()
            ->buildProxy()
            ->buildCookies()
            ->buildHeaders();

        $this->response = new Response();

        return $this->makeCall();
    }

    /**
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
     * Definie le timeout de la requete
     *
     * @param int $timeout
     *
     * @return $this
     */
    abstract public function setTimeout($timeout);

    /**
     * @return \Neutrino\HttpClient\Response
     */
    abstract protected function makeCall();

    /**
     * Construit les parametres de la requete.
     *
     * @return $this
     */
    abstract protected function buildParams();

    /**
     * Construit les headers de la requete.
     *
     * @return $this
     */
    abstract protected function buildHeaders();

    /**
     * Construit le proxy de la requete
     *
     * @return $this
     */
    abstract protected function buildProxy();

    /**
     * Construit les cookies de la requete
     *
     * @return $this
     */
    abstract protected function buildCookies();
}
