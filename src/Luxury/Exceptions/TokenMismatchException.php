<?php

namespace Luxury\Exceptions;

use Phalcon\Exception;

class TokenMismatchException extends Exception
{
    public function __construct($message = 'Token mismatch', $code = 403, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}