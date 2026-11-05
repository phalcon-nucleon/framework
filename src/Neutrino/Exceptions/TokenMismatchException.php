<?php

declare(strict_types=1);

namespace Neutrino\Exceptions;

use RuntimeException;
use Throwable;

/**
 * A failed CSRF check, for applications that throw instead of answering 403.
 */
class TokenMismatchException extends RuntimeException
{
    public function __construct(string $message = 'Token mismatch', int $code = 403, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
