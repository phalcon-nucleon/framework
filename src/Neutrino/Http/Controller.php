<?php

declare(strict_types=1);

namespace Neutrino\Http;

use Neutrino\Constants\Services;
use Neutrino\Foundation\Middleware\Controller as ControllerMiddleware;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Router;
use UnexpectedValueException;

/**
 * Base controller: attaches the middlewares declared on the matched route (`paths['middleware']`).
 *
 * Route middlewares, in the paths of a route:
 * - `'middleware' => Ajax::class`
 * - `'middleware' => [Ajax::class, Other::class]`
 * - `'middleware' => [Throttle::class => [10, 60], Ajax::class]` (constructor parameters)
 *
 * @property-read \Neutrino\Foundation\Http\Kernel $application
 * @property-read \Neutrino\Auth\Manager           $auth
 * @property-read \Phalcon\Config\Config           $config
 */
abstract class Controller extends \Phalcon\Mvc\Controller
{
    /**
     * Called when the controller is built: register the controller middlewares here.
     *
     * No return type, so that controllers can override it as before.
     *
     * @return void
     */
    protected function onConstruct()
    {
        $this->routeMiddleware();
    }

    /**
     * Attaches the middlewares declared on the matched route, for the dispatched action only.
     */
    protected function routeMiddleware(): void
    {
        $di = $this->getDI();
        /** @var Router $router */
        $router = $di->getShared(Services::ROUTER);
        /** @var Dispatcher $dispatcher */
        $dispatcher = $di->getShared(Services::DISPATCHER);

        if ($dispatcher->wasForwarded() || !$router->wasMatched()) {
            return;
        }

        $paths = $router->getMatchedRoute()?->getPaths() ?? [];

        if (empty($paths['middleware'])) {
            return;
        }

        $action = $dispatcher->getActionName();

        // The stubs type the paths as scalars, but they can hold arrays.
        /** @var mixed $declared */
        $declared = $paths['middleware'];
        /** @var array<int|string, mixed> $middlewares */
        $middlewares = is_array($declared) ? $declared : [$declared];

        foreach ($middlewares as $key => $middleware) {
            [$class, $params] = is_int($key) ? [$middleware, []] : [$key, is_array($middleware) ? array_values($middleware) : [$middleware]];

            if (!is_string($class) || !is_subclass_of($class, ControllerMiddleware::class)) {
                throw new UnexpectedValueException(static::class . ': a route middleware must extend ' . ControllerMiddleware::class . ', ' . (is_string($class) ? $class : get_debug_type($class)) . ' given.');
            }

            $this->middleware($class, ...$params)->only([$action]);
        }
    }

    /**
     * Attaches a controller middleware.
     *
     * Controller middlewares are built with the controller, after the start of the dispatch:
     * they cannot listen to `application:boot`, `dispatch:beforeDispatchLoop` nor `dispatch:beforeDispatch`.
     *
     * @template T of ControllerMiddleware
     *
     * @param class-string<T> $middlewareClass
     *
     * @return T
     */
    protected function middleware(string $middlewareClass, mixed ...$params): ControllerMiddleware
    {
        $middleware = new $middlewareClass(static::class, ...$params);

        /** @var \Neutrino\Foundation\Http\Kernel $application */
        $application = $this->getDI()->getShared(Services::APP);
        $application->attach($middleware);

        return $middleware;
    }
}
