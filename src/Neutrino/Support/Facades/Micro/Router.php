<?php

declare(strict_types=1);

namespace Neutrino\Support\Facades\Micro;

use Neutrino\Constants\Services;
use Neutrino\Support\Facades\Facade;

/**
 * Facade of the Micro router (`micro.router`).
 *
 * @see \Neutrino\Micro\Router
 *
 * @method static \Phalcon\Mvc\Router\RouteInterface add(string $pattern, \Closure|string|array<int|string, mixed> $handler, string|array<string>|null $httpMethods = null)
 * @method static \Phalcon\Mvc\Router\RouteInterface addGet(string $pattern, \Closure|string|array<int|string, mixed> $handler)
 * @method static \Phalcon\Mvc\Router\RouteInterface addPost(string $pattern, \Closure|string|array<int|string, mixed> $handler)
 * @method static \Phalcon\Mvc\Router\RouteInterface addPut(string $pattern, \Closure|string|array<int|string, mixed> $handler)
 * @method static \Phalcon\Mvc\Router\RouteInterface addPatch(string $pattern, \Closure|string|array<int|string, mixed> $handler)
 * @method static \Phalcon\Mvc\Router\RouteInterface addDelete(string $pattern, \Closure|string|array<int|string, mixed> $handler)
 * @method static \Phalcon\Mvc\Router\RouteInterface addOptions(string $pattern, \Closure|string|array<int|string, mixed> $handler)
 * @method static \Phalcon\Mvc\Router\RouteInterface addHead(string $pattern, \Closure|string|array<int|string, mixed> $handler)
 * @method static \Neutrino\Micro\Router notFound(callable $handler)
 * @method static \Neutrino\Micro\Router mount(\Phalcon\Mvc\Micro\CollectionInterface $collection)
 * @method static array<\Phalcon\Mvc\Router\RouteInterface> getRoutes()
 * @method static \Phalcon\Mvc\Router\RouteInterface|null getRouteByName(string $name)
 * @method static bool wasMatched()
 * @method static \Phalcon\Mvc\Router\RouteInterface|null getMatchedRoute()
 * @method static array<int|string, mixed> getParams()
 * @method static string getControllerName()
 * @method static string getActionName()
 */
class Router extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return Services::MICRO_ROUTER;
    }
}
