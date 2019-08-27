<?php

namespace Neutrino\Exceptions;

use Neutrino\Http\Standards\StatusCode;
use Phalcon\Exception;
use Phalcon\Http\Response;
use Throwable;

/**
 * Class ThrottledException
 * @package Neutrino\Exceptions
 */
class ThrottledException extends Exception
{
    private $max;

    private $available_in;

    public function __construct($max, $available_in, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->max = $max;
        $this->available_in = $available_in;
    }

    /**
     * @return \Phalcon\Http\ResponseInterface
     */
    public function createResponse()
    {
        $code = StatusCode::TOO_MANY_REQUESTS;
        $msg = StatusCode::message($code);

        return (new Response($msg, $code, $msg))
          ->setHeader('X-RateLimit-Limit', $this->max)
          ->setHeader('X-RateLimit-Remaining', 0)
          ->setHeader('Retry-After', $this->available_in);
    }
}
