<?php

namespace Luxury\Exceptions;

use Phalcon\Exception;

class TokenMismatchException extends Exception
{
    /**
     * TokenMismatchException constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = 'Token mismatch', $code = 403, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
