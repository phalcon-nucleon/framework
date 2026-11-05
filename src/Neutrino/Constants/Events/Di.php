<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events;

/**
 * Events of the 'di' space, fired on the container's internal events manager.
 */
final class Di
{
    public const string BEFORE_SERVICE_RESOLVE = 'di:beforeServiceResolve';
    public const string AFTER_SERVICE_RESOLVE  = 'di:afterServiceResolve';
}
