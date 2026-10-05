<?php

declare(strict_types=1);

namespace Neutrino\Providers\Http;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Mvc\Router as MvcRouter;

/**
 * HTTP router, without the default routes. The kernel passes it the request URI.
 */
class Router extends Provider
{
    protected string $name = Services::ROUTER;

    protected bool $shared = true;

    protected array $aliases = [MvcRouter::class];

    protected function register(): MvcRouter
    {
        return new MvcRouter(false);
    }
}
