<?php

namespace Neutrino\Exceptions;

use Phalcon\Exception;

/**
 * Class TokenMismatchException
 *
 * Token exception for failing csrf check
 *
 *  @package Neutrino\Exceptions
 */
class TokenMismatchException extends Exception
{
    /**
     * TokenMismatchException constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = 'Token mismatch', $code = 419, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
