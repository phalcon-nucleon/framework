<?php

namespace Luxury\Http\Middleware;

use Luxury\Constants\Events as EventSpaces;
use Luxury\Foundation\Middleware\Controller as ControllerMiddleware;
use Luxury\Http\Routing\ThrottleRequest;
use Luxury\Middleware\AfterMiddleware;
use Luxury\Middleware\BeforeMiddleware;
use Luxury\Security\RateLimiter;
use Phalcon\Http\Response\StatusCode;

/**
 * Class Throttle
 *
 * @package Luxury\Middleware
 */
class ThrottleMiddleware extends ControllerMiddleware implements BeforeMiddleware, AfterMiddleware
{
    /**
     * The throttle handler
     *
     * @var ThrottleRequest
     */
    private $throttler;

    /**
     * Throttle constructor.
     *
     * @param int $max   Number of max request by $decay
     * @param int $decay Decay time (seconds)
     */
    public function __construct($max, $decay = 60)
    {
        parent::__construct();

        $this->throttler = new ThrottleRequest($max, $decay);
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
    public function before($event, $source, $data = null)
    {
        $signature = $this->resolveRequestSignature();
        if (!$this->throttler->handle($signature)) {
            $this->throttler->addHeader($signature, true);

            return false;
        }

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
    public function after($event, $source, $data = null)
    {
        $this->throttler->addHeader($this->resolveRequestSignature(), false);
    }

    /**
     * Resolve the request signature based on :
     *  Module : Namespace : Controller : Action | HOST | URI | ClientIP
     *
     * @return string
     */
    private function resolveRequestSignature()
    {
        $request = $this->request;
        $router  = $this->router;

        $signature =
            $router->getModuleName() .
            ':' . $router->getNamespaceName() .
            ':' . $router->getControllerName() .
            ':' . $router->getActionName() .
            '|' . $request->getHttpHost() .
            '|' . $request->getURI() .
            '|' . $request->getClientAddress();

        return sha1($signature);
    }
}
