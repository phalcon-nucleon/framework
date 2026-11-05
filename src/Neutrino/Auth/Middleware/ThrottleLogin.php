<?php

declare(strict_types=1);

namespace Neutrino\Auth\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Middleware\Throttle;

/**
 * Limits the login attempts: `'middleware' => [ThrottleLogin::class => [$max, $decaySeconds]]`.
 */
class ThrottleLogin extends Throttle
{
    protected string $name = Services::AUTH;
}
