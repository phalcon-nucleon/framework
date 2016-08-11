<?php

namespace Luxury\Http\Routing;

use Luxury\Security\RateLimiter;
use Luxury\Di\Injectable;
use Phalcon\Http\Response\StatusCode;

class ThrottleRequest extends Injectable
{
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
     * @var RateLimiter
     */
    private $limiter;

    /**
     * Throttle constructor.
     *
     * @param int $max   Number of max request by $decay
     * @param int $decay Decay time (seconds)
     */
    public function __construct($max, $decay = 60)
    {
        parent::__construct();

        $this->max     = $max;
        $this->decay   = $decay;
        $this->limiter = new RateLimiter();
    }

    /**
     * Check if there are too many attempts & hit the cache
     *
     * @param $signature
     *
     * @return bool
     */
    public function handle($signature)
    {
        if ($this->limiter->tooManyAttempts($signature, $this->max, $this->decay)) {
            return false;
        }

        $this->limiter->hit($signature, $this->decay);

        return true;
    }

    /**
     * Add the limit header information to the response.
     *
     * @param string $signature
     * @param bool   $tooManyAttempts Bind specific values when there are too many attempts
     */
    public function addHeader($signature, $tooManyAttempts = false)
    {
        $response = $this->response;

        $response->setHeader('X-RateLimit-Limit', $this->max);
        $response->setHeader(
            'X-RateLimit-Remaining',
            $this->limiter->retriesLeft($signature, $this->max, $this->decay)
        );

        if ($tooManyAttempts) {
            $response->setHeader('X-RateLimit-Remaining', 0);

            $msg = StatusCode::message(StatusCode::TOO_MANY_REQUESTS);

            $response->setContent($msg);
            $response->setStatusCode(StatusCode::TOO_MANY_REQUESTS, $msg);
            $response->setHeader('Retry-After', $this->limiter->availableIn($signature, $this->decay));
        }
    }
}
