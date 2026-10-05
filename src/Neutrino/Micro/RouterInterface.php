<?php

declare(strict_types=1);

namespace Neutrino\Micro;

use Closure;
use Phalcon\Mvc\Micro\CollectionInterface;
use Phalcon\Mvc\Router\RouteInterface;

/**
 * Routes of the Micro kernel.
 *
 * A handler is one of:
 * - a closure, bound to the application;
 * - `'Controller::action'` or `[Controller::class, 'action']`;
 * - `['controller' => Controller::class, 'action' => 'action', 'middlewares' => [...]]`, with controller middlewares
 *   (`Middleware::class`, or `Middleware::class => [constructor parameters]`).
 *
 * @phpstan-type Handler Closure|string|array<int|string, mixed>
 */
interface RouterInterface
{
    /**
     * @param Handler                   $handler
     * @param string|list<string>|null  $httpMethods `null`: every method
     */
    public function add(string $pattern, Closure|string|array $handler, string|array|null $httpMethods = null): RouteInterface;

    /** @param Handler $handler */
    public function addGet(string $pattern, Closure|string|array $handler): RouteInterface;

    /** @param Handler $handler */
    public function addPost(string $pattern, Closure|string|array $handler): RouteInterface;

    /** @param Handler $handler */
    public function addPut(string $pattern, Closure|string|array $handler): RouteInterface;

    /** @param Handler $handler */
    public function addPatch(string $pattern, Closure|string|array $handler): RouteInterface;

    /** @param Handler $handler */
    public function addDelete(string $pattern, Closure|string|array $handler): RouteInterface;

    /** @param Handler $handler */
    public function addOptions(string $pattern, Closure|string|array $handler): RouteInterface;

    /** @param Handler $handler */
    public function addHead(string $pattern, Closure|string|array $handler): RouteInterface;

    /**
     * Handler called when no route matches.
     */
    public function notFound(callable $handler): static;

    public function mount(CollectionInterface $collection): static;

    /**
     * @return array<RouteInterface>
     */
    public function getRoutes(): array;

    public function getRouteByName(string $name): ?RouteInterface;

    public function wasMatched(): bool;

    public function getMatchedRoute(): ?RouteInterface;

    /**
     * @return array<int|string, mixed>
     */
    public function getParams(): array;

    public function getControllerName(): string;

    public function getActionName(): string;
}
