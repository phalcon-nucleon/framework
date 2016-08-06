<?php

namespace Luxury\Exceptions;

use Phalcon\Exception;

/**
 * Class SessionAdapterNotFound
 *
 * @package Luxury\Exceptions
 */
final class SessionAdapterNotFound extends Exception
{

    /**
     * SessionAdapterNotFound constructor.
     *
     * @param string          $class
     * @param \Exception|null $previous
     */
    public function __construct($class, \Exception $previous = null)
    {
        \Exception::__construct("", 448, $previous);
    }
}
