<?php

declare(strict_types=1);

namespace Neutrino\Test;

use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers\Http\Router;
use Neutrino\Support\Facades\Facade;
use Neutrino\Test\Helpers\RoutesTrait;
use Phalcon\Di\Di;
use Phalcon\Mvc\Router\RouteInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Tests the HTTP routes of the application: each route returned by {@see RoutesTestCase::routes()} is asserted,
 * then every route of `routes/http.php` must have been matched by one of them.
 */
abstract class RoutesTestCase extends FuncTestCase
{
    use RoutesTrait;

    /**
     * Routes to test, built with {@see RoutesTestCase::formatDataRoute()}.
     *
     * @return list<array{string, string, bool, ?string, ?string, ?array<string, mixed>}>
     */
    abstract protected static function routes(): array;

    /**
     * Routes declared by `routes/http.php`, by pattern.
     *
     * @return array<string, array{RouteInterface}>
     */
    public static function getApplicationRoutes(): array
    {
        $di = new Di();
        Di::setDefault($di);

        Facade::clearResolvedInstances();
        Facade::setDependencyInjection($di);

        try {
            ProviderRegistrar::register($di, [Router::class]);

            require BASE_PATH . '/routes/http.php';

            /** @var \Phalcon\Mvc\Router $router */
            $router = $di->getShared('router');

            $routes = [];
            /** @var RouteInterface $route */
            foreach ($router->getRoutes() as $route) {
                $routes[$route->getPattern()] = [$route];
            }

            return $routes;
        } finally {
            Facade::clearResolvedInstances();
            Di::reset();
        }
    }

    /**
     * @param string                    $route      URI, relative to `app.base_uri`
     * @param string                    $method     HTTP method
     * @param bool                      $expected   Whether a route must match
     * @param string|null               $controller Expected controller
     * @param string|null               $action     Expected action
     * @param array<string, mixed>|null $params     Expected route parameters
     *
     * @return array{string, string, bool, ?string, ?string, ?array<string, mixed>}
     */
    public static function formatDataRoute(
        string $route,
        string $method,
        bool $expected,
        ?string $controller = null,
        ?string $action = null,
        ?array $params = null,
    ): array {
        return [$route, $method, $expected, $controller, $action, $params];
    }

    /**
     * @return array<string, array{string, string, bool, ?string, ?string, ?array<string, mixed>}>
     */
    public static function routesProvider(): array
    {
        $routes = [];
        foreach (static::routes() as $i => $route) {
            $routes[$route[1] . '-' . $route[0] . '-' . ($route[2] ? 'true' : 'false') . '-' . $i] = $route;
        }

        return $routes;
    }

    /**
     * @param array<string, mixed>|null $params
     */
    #[DataProvider('routesProvider')]
    public function testRoutes(
        string $route,
        string $method,
        bool $expected,
        ?string $controller = null,
        ?string $action = null,
        ?array $params = null,
    ): void {
        $this->assertRoute($route, $method, $expected, $controller, $action, $params);
    }

    #[DataProvider('getApplicationRoutes')]
    #[Depends('testRoutes')]
    public function testRoutesTested(RouteInterface $route): void
    {
        if (!array_key_exists($route->getPattern(), self::$testedRoutes)) {
            $this->markTestIncomplete('Route "' . $route->getPattern() . '" has not been tested');
        }

        $this->assertEquals(
            self::routeToArray($route),
            self::routeToArray(self::$testedRoutes[$route->getPattern()]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function routeToArray(RouteInterface $route): array
    {
        return [
            'HttpMethods'     => $route->getHttpMethods(),
            'Hostname'        => $route->getHostname(),
            'Name'            => $route->getName(),
            'Pattern'         => $route->getPattern(),
            'CompiledPattern' => $route->getCompiledPattern(),
            'Paths'           => $route->getPaths(),
        ];
    }
}
