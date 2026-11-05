<?php

declare(strict_types=1);

namespace Neutrino\Http\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Middleware\Throttle;

/**
 * Limits the requests: `'middleware' => [ThrottleRequest::class => [$max, $decaySeconds]]`.
 */
class ThrottleRequest extends Throttle
{
    protected string $name = Services::REQUEST;
}
