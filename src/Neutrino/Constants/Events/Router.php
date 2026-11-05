<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events;

/**
 * Events of the 'router' space (Phalcon\Mvc\Router).
 */
final class Router
{
    public const string BEFORE_CHECK_ROUTES = 'router:beforeCheckRoutes';
    public const string BEFORE_CHECK_ROUTE  = 'router:beforeCheckRoute';
    public const string MATCHED_ROUTE       = 'router:matchedRoute';
    public const string NOT_MATCHED_ROUTE   = 'router:notMatchedRoute';
    public const string AFTER_CHECK_ROUTES  = 'router:afterCheckRoutes';
    public const string BEFORE_MOUNT        = 'router:beforeMount';
}
