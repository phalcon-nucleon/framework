<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Http\Exception;

use RuntimeException;

/**
 * A route cannot be written to the routes cache (closure, converter, beforeMatch…).
 */
final class UncacheableRouteException extends RuntimeException
{
    public function __construct(public readonly string $pattern, public readonly string $reason)
    {
        parent::__construct(sprintf('Route "%s" cannot be cached: %s.', $pattern, $reason));
    }
}
