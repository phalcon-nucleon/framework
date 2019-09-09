<?php

namespace Neutrino\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Exceptions\ThrottledException;
use Neutrino\Foundation\Middleware\Controller as ControllerMiddleware;
use Neutrino\Http\Standards\StatusCode;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Security\RateLimiter;
use Phalcon\Events\Event;

/**
 * Class Throttle
 *
 * @package Neutrino\Middleware
 */
abstract class Throttle extends ControllerMiddleware implements BeforeInterface, AfterInterface
{
    /**
     * Name of the throttle limiter.
     *
     * @var string
     */
    protected $name;

    /**
     * Number of max request by $decay
     *
     * @var int
     */
    private $max;

    /**
     * Decay time (seconds)
     *
     * @var int
     */
    private $decay;

    /**
     * The Rate Limiter
     *
     * @var \Neutrino\Security\RateLimiter
     */
    private $limiter;

    /**
     * Throttle constructor.
     *
     * @param string $controllerClass
     * @param int    $max   Number of max request by $decay
     * @param int    $decay Decay time (seconds)
     */
    public function __construct($controllerClass, $max, $decay = 60)
    {
        parent::__construct($controllerClass);

        if (!isset($this->name)) {
            throw new \RuntimeException(static::class . '->name is empty.');
        }

        $this->max = $max;
        $this->decay = $decay;
    }

    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function before(Event $event, $source, $data = null)
    {
        $signature = $this->resolveRequestSignature();

        $limiter = $this->getLimiter();

        if ($limiter->tooManyAttempts($signature, $this->max, $this->decay)) {
            throw new ThrottledException($this->max, $limiter->availableIn($signature, $this->decay));
        }

        $limiter->hit($signature, $this->decay);

        $this->addHeader($this->resolveRequestSignature());

        return true;
    }

    /**
     * Called after the execution of handler
     *
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function after(Event $event, $source, $data = null)
    {
        $this->addHeader($this->resolveRequestSignature());

        return true;
    }

    /**
     * Resolve the request signature based on :
     *  Module : Namespace : Controller : Action | HOST | URI | ClientIP
     *
     * @return string
     */
    protected function resolveRequestSignature()
    {
        /** @var \Phalcon\Http\Request $request */
        $request = $this->getDI()->getShared(Services::REQUEST);
        /** @var \Phalcon\Mvc\Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);

        return crc32(
            $router->getModuleName() .
            ':' . $router->getNamespaceName() .
            ':' . $router->getControllerName() .
            ':' . $router->getActionName() .
            '|' . $request->getHttpHost() .
            '|' . $request->getURI() .
            '|' . $request->getClientAddress()
        );
    }

    /**
     * Add the limit header information to the response.
     *
     * @param string $signature
     */
    protected function addHeader($signature)
    {
        /** @var \Phalcon\Http\Response $response */
        $response = $this->getDI()->getShared(Services::RESPONSE);

        $limiter = $this->getLimiter();

        $response
          ->setHeader('X-RateLimit-Limit', $this->max)
          ->setHeader(
            'X-RateLimit-Remaining',
            $limiter->retriesLeft($signature, $this->max, $this->decay)
          );
    }

    /**
     * Bind and return the limiter instance.
     *
     * @return \Neutrino\Security\RateLimiter
     */
    protected function getLimiter()
    {
        if (!isset($this->limiter)) {
            $this->limiter = $this->getDI()->get(RateLimiter::class, [$this->name]);
        }

        return $this->limiter;
    }
}
