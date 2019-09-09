<?php


namespace Neutrino\Exceptions;

use Phalcon\Exception;

/**
 * Class AjaxMismatchException
 * @package Neutrino\Exceptions
 */
class AjaxMismatchException extends Exception
{
    /**
     * AjaxMismatchException constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = 'Ajax mismatch', $code = 420, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}