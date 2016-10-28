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
     * @param \Throwable|null $previous
     */
    public function __construct($class, \Throwable $previous = null)
    {
        parent::__construct("Session Adapter $class not found", 448, $previous);
    }
}
