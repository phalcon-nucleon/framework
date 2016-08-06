<?php

namespace Luxury\Http\Middleware;

use Luxury\Constants\Events as EventSpaces;
use Luxury\Constants\Services;
use Luxury\Foundation\Middleware\Controller as ControllerMiddleware;
use Luxury\Middleware\BeforeMiddleware;

/**
 * Class Throttle
 *
 * @package     Luxury\Middleware
 */
class Throttle extends ControllerMiddleware implements BeforeMiddleware
{
    /**
     * Number of max request by $decay
     *
     * @var int
     */
    private $max;

    /**
     * TODO
     *
     * @var int
     */
    private $decay;

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

        /** @var \Phalcon\Cache\BackendInterface $cache */
        $cache = $this->{Services::CACHE};

        $attempts = $cache->get($signature);

        $attempts = intval($attempts);

        if ($attempts > $this->max) {
            $this->buildResponse($attempts, true);
            // TODO
            throw new \Exception;
        }

        $attempts++;

        $this->buildResponse($attempts, false);

        $cache->save($signature, $attempts, $this->decay * 60);
    }

    /**
     * Resolve the request signature based on :
     *  HOST . URI . ClientIP
     *
     * @return string
     */
    private function resolveRequestSignature()
    {
        $request = $this->request;
        $router  = $this->router;

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

    private function buildResponse($attempts, $retry = null)
    {
        $response = $this->response;

        $response->setHeader('X-RateLimit-Limit', $this->max);
        $response->setHeader('X-RateLimit-Remaining', $this->max - $attempts);

        if (!is_null($retry)) {
            $response->setHeader('Retry-After', $this->decay);
        }
    }
}
