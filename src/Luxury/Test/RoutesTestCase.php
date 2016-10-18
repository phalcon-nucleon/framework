<?php
/**
 * Created by PhpStorm.
 * User: gallegret
 * Date: 07/07/2016
 * Time: 15:08
 */

namespace Luxury\Test;

use Luxury\Test\Helpers\RoutesTrait;

/**
 * Class RoutesTestCase
 *
 * @package Luxury\Test
 */
abstract class RoutesTestCase extends FuncTestCase
{
    use RoutesTrait;
    
    protected static $testedRoutes = [];

    /**
     * @return array
     */
    public function routesProvider()
    {
        $routes = $this->routes();

        $_routes = [];
        foreach ($routes as $route) {
            $_routes[$route[0]] = $route;
        }

        return $_routes;
    }

    /**
     * @test
     * @dataProvider routesProvider
     *
     * @param       $route
     * @param       $method
     * @param       $expected
     * @param null  $controller
     * @param null  $action
     * @param array $params
     */
    public function testRoutes(
        $route,
        $method,
        $expected,
        $controller = null,
        $action = null,
        array $params = null
    ) {
        $this->assertRoute($route, $method, $expected, $controller, $action, $params);
    }

    /**
     * @return mixed
     */
    public function getRoutes()
    {
        $routes = [];
        foreach (self::staticKernel()->router->getRoutes() as $route) {
            /** @var \Phalcon\Mvc\Router\Route $route */
            $routes[$route->getPattern()] = [$route];
        }

        return $routes;
    }

    /**
     * @test
     * @dataProvider      getRoutes
     * @depends           testRoutes
     *
     * @param \Phalcon\Mvc\Router\RouteInterface $route
     *
     */
    public function testRoutesTested($route = null)
    {
        if (!array_key_exists($route->getPattern(), self::$testedRoutes)) {
            $this->markTestIncomplete('Route "' . $route->getPattern() . '" has not been testing');

            return;
        }

        $this->assertArrayHasKey($route->getPattern(), self::$testedRoutes);
        $this->assertEquals(
            $this->routeToArray($route),
            $this->routeToArray(self::$testedRoutes[$route->getPattern()])
        );
    }

    /**
     * @return array[]
     */
    abstract protected function routes(): array;

    /**
     * @param \Phalcon\Mvc\Router\RouteInterface $route
     *
     * @return array
     */
    private function routeToArray($route)
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
