<?php

declare(strict_types=1);

namespace Neutrino\Micro;

use Closure;
use Neutrino\Constants\Events;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Middleware\Controller as ControllerMiddleware;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Phalcon\Di\DiInterface;
use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\CollectionInterface;
use Phalcon\Mvc\Router\RouteInterface;
use Phalcon\Mvc\RouterInterface as MvcRouterInterface;
use UnexpectedValueException;

/**
 * Routes of the Micro kernel, registered on the application (`micro.router` service).
 *
 * @phpstan-import-type Handler from RouterInterface
 */
class Router extends Injectable implements RouterInterface
{
    public function add(string $pattern, Closure|string|array $handler, string|array|null $httpMethods = null): RouteInterface
    {
        $route = $this->application()->map($pattern, $this->toHandler($handler));

        if ($httpMethods !== null) {
            $route->via($httpMethods);
        }

        return $route;
    }

    public function addGet(string $pattern, Closure|string|array $handler): RouteInterface
    {
        return $this->application()->get($pattern, $this->toHandler($handler));
    }

    public function addPost(string $pattern, Closure|string|array $handler): RouteInterface
    {
        return $this->application()->post($pattern, $this->toHandler($handler));
    }

    public function addPut(string $pattern, Closure|string|array $handler): RouteInterface
    {
        return $this->application()->put($pattern, $this->toHandler($handler));
    }

    public function addPatch(string $pattern, Closure|string|array $handler): RouteInterface
    {
        return $this->application()->patch($pattern, $this->toHandler($handler));
    }

    public function addDelete(string $pattern, Closure|string|array $handler): RouteInterface
    {
        return $this->application()->delete($pattern, $this->toHandler($handler));
    }

    public function addOptions(string $pattern, Closure|string|array $handler): RouteInterface
    {
        return $this->application()->options($pattern, $this->toHandler($handler));
    }

    public function addHead(string $pattern, Closure|string|array $handler): RouteInterface
    {
        return $this->application()->head($pattern, $this->toHandler($handler));
    }

    public function notFound(callable $handler): static
    {
        $this->application()->notFound($handler);

        return $this;
    }

    public function mount(CollectionInterface $collection): static
    {
        $this->application()->mount($collection);

        return $this;
    }

    public function getRoutes(): array
    {
        return $this->router()->getRoutes();
    }

    public function getRouteByName(string $name): ?RouteInterface
    {
        $route = $this->router()->getRouteByName($name);

        return $route instanceof RouteInterface ? $route : null;
    }

    public function wasMatched(): bool
    {
        return $this->router()->wasMatched();
    }

    public function getMatchedRoute(): ?RouteInterface
    {
        return $this->router()->getMatchedRoute();
    }

    public function getParams(): array
    {
        return $this->router()->getParams();
    }

    public function getControllerName(): string
    {
        return $this->router()->getControllerName();
    }

    public function getActionName(): string
    {
        return $this->router()->getActionName();
    }

    /**
     * @param Handler $handler
     */
    protected function toHandler(Closure|string|array $handler): Closure
    {
        if ($handler instanceof Closure) {
            return $handler;
        }

        [$controller, $action, $middlewares] = $this->parseHandler($handler);

        $di = $this->getDI();
        $application = $this->application();

        // Not static: Micro binds the closure handlers to the application.
        return function (mixed ...$args) use ($di, $application, $controller, $action, $middlewares): mixed {
            $instance = $di->get($controller);

            if (!is_object($instance) || !method_exists($instance, $action)) {
                throw new UnexpectedValueException('Method "' . $action . '" does not exist on "' . $controller . '".');
            }

            $instances = [];
            foreach ($middlewares as $middleware => $params) {
                $instances[] = self::makeMiddleware($controller, $middleware, $params);
            }

            return self::run($di, $application, $instances, $instance, $action, $args);
        };
    }

    /**
     * Runs the controller middlewares around the action.
     *
     * @param list<ControllerMiddleware> $middlewares
     * @param array<int|string, mixed>   $args
     */
    private static function run(DiInterface $di, Micro $application, array $middlewares, object $controller, string $action, array $args): mixed
    {
        $before = null;
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof BeforeInterface) {
                $before ??= new Event(Events\Micro::BEFORE_EXECUTE_ROUTE, $application);

                if ($middleware->before($before, $application, null) === false) {
                    return $di->getShared(Services::RESPONSE);
                }
            }
        }

        $value = $controller->$action(...$args);

        $after = null;
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof AfterInterface) {
                $after ??= new Event(Events\Micro::AFTER_EXECUTE_ROUTE, $application);

                $middleware->after($after, $application, null);
            }
        }

        return $value;
    }

    /**
     * Same rule as the HTTP route middlewares: an int key means the value is the class;
     * a string key is the class, and the value its parameters (a single parameter is accepted).
     */
    private static function makeMiddleware(string $controller, int|string $middleware, mixed $params): ControllerMiddleware
    {
        [$class, $params] = is_int($middleware)
            ? [$params, []]
            : [$middleware, is_array($params) ? $params : [$params]];

        if (!is_string($class) || !is_subclass_of($class, ControllerMiddleware::class)) {
            throw new UnexpectedValueException('A controller middleware must extend ' . ControllerMiddleware::class . ', ' . (is_string($class) ? $class : get_debug_type($class)) . ' given.');
        }

        return new $class($controller, ...array_values($params));
    }

    /**
     * @param string|array<int|string, mixed> $handler
     *
     * @return array{string, string, array<int|string, mixed>}
     */
    private function parseHandler(string|array $handler): array
    {
        if (is_string($handler)) {
            $handler = explode('::', $handler, 2);
        }

        if (array_is_list($handler)) {
            $handler = ['controller' => $handler[0] ?? null, 'action' => $handler[1] ?? null];
        }

        $controller = $handler['controller'] ?? null;
        $action = $handler['action'] ?? null;
        $middlewares = $handler['middlewares'] ?? [];

        if (!is_string($controller) || $controller === '' || !is_string($action) || $action === '' || !is_array($middlewares)) {
            throw new UnexpectedValueException('Invalid Micro route handler: expected a closure, "Controller::action", [Controller::class, "action"] or ["controller" => …, "action" => …].');
        }

        /** @var array<int|string, mixed> $middlewares */
        return [$controller, $action, $middlewares];
    }

    private function application(): Micro
    {
        /** @var Micro */
        return $this->getDI()->getShared(Services::APP);
    }

    private function router(): MvcRouterInterface
    {
        /** @var MvcRouterInterface */
        return $this->getDI()->getShared(Services::ROUTER);
    }
}
