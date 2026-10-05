<?php

declare(strict_types=1);

namespace Neutrino\Test\Helpers;

use Neutrino\Constants\Services;
use Phalcon\Config\Config;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\RouteInterface;

/**
 * Asserts how the application router handles a URI.
 *
 * The using class must provide `getDI()` (as {@see \Neutrino\Test\TestCase}).
 */
trait RoutesTrait
{
    /**
     * Routes matched by {@see RoutesTrait::assertRoute()}, by pattern.
     *
     * @var array<string, RouteInterface>
     */
    protected static array $testedRoutes = [];

    /**
     * @param string                    $route      URI, relative to `app.base_uri`
     * @param bool                      $expected   Whether a route must match
     * @param string|null               $controller Expected controller (`null`: not checked when a route matches)
     * @param string|null               $action     Expected action (`null`: not checked when a route matches)
     * @param array<string, mixed>|null $params     Expected route parameters (all of them)
     */
    public function assertRoute(
        string $route,
        string $method,
        bool $expected,
        ?string $controller = null,
        ?string $action = null,
        ?array $params = null,
    ): void {
        // GIVEN
        $di = $this->getDI();
        /** @var Router $router */
        $router = $di->getShared(Services::ROUTER);
        /** @var Config $config */
        $config = $di->getShared(Services::CONFIG);

        $base = $config->path('app.base_uri', '/');
        $base = is_string($base) ? $base : '/';
        $uri = $base . preg_replace('#^/(.+)#', '$1', $route);

        // WHEN
        $previousMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = $method;
        try {
            $router->handle($uri);
        } finally {
            if ($previousMethod === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $previousMethod;
            }
        }

        // THEN
        $matched = $router->wasMatched();
        self::assertSame($expected, $matched, sprintf('Failed asserting that %s %s %s.', $method, $uri, $expected ? 'matches a route' : 'matches no route'));

        $matchedRoute = $router->getMatchedRoute();
        if ($matched && $matchedRoute !== null) {
            self::$testedRoutes[$matchedRoute->getPattern()] = $matchedRoute;
        }

        foreach (['Controller' => [$controller, $router->getControllerName()], 'Action' => [$action, $router->getActionName()]] as $name => [$expectedName, $actualName]) {
            // Not checked when a route must match and no name is given.
            if (!$expected || $expectedName !== null) {
                self::assertEquals($expectedName, $actualName, "Failed asserting the $name name of $method $uri.");
            }
        }

        if ($expected && $matched && $params !== null) {
            self::assertEquals($params, $router->getParams(), "Failed asserting the parameters of $method $uri.");
        }
    }
}
