<?php

namespace Luxury\Middleware;

use Luxury\Constants\Services;
use Luxury\Foundation\Middleware\Controller as ControllerMiddleware;
use Luxury\Http\Standards\StatusCode;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Security\RateLimiter;
use Phalcon\Events\Event;

/**
 * Class Throttle
 *
 * @package     Luxury\Middleware
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
     * @var \Luxury\Security\RateLimiter
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

        $this->max   = $max;
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
            $this->addHeader($signature, true);

            return false;
        }

        $limiter->hit($signature, $this->decay);

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
        $this->addHeader($this->resolveRequestSignature(), false);

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
        $request = $this->{Services::REQUEST};
        /** @var \Phalcon\Mvc\Router $router */
        $router  = $this->{Services::ROUTER};

        return sha1(
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
     * @param bool   $tooManyAttempts Bind specific values when there are too many attempts
     */
    protected function addHeader($signature, $tooManyAttempts = false)
    {
        /** @var \Phalcon\Http\Response $response */
        $response = $this->{Services::RESPONSE};

        $limiter = $this->getLimiter();

        $response->setHeader('X-RateLimit-Limit', $this->max);
        $response->setHeader(
            'X-RateLimit-Remaining',
            $limiter->retriesLeft($signature, $this->max, $this->decay)
        );

        if ($tooManyAttempts) {
            $response->setHeader('X-RateLimit-Remaining', 0);

            $msg = StatusCode::message(StatusCode::TOO_MANY_REQUESTS);

            $response->setContent($msg);
            $response->setStatusCode(StatusCode::TOO_MANY_REQUESTS, $msg);
            $response->setHeader(
                'Retry-After',
                $limiter->availableIn($signature, $this->decay)
            );
        }
    }

    /**
     * Bind and return the limiter instance.
     *
     * @return \Luxury\Security\RateLimiter
     */
    protected function getLimiter()
    {
        if (!isset($this->limiter)) {
            $this->limiter = new RateLimiter($this->name);
        }

        return $this->limiter;
    }
}
