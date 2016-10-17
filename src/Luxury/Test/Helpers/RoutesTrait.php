<?php
/**
 * Created by PhpStorm.
 * User: gallegret
 * Date: 07/07/2016
 * Time: 15:08
 */

namespace Luxury\Test\Helpers;

use Luxury\Constants\Services;

/**
 * Class RoutesTestCase
 *
 * @package Luxury\Test
 */
trait RoutesTrait
{
    protected static $testedRoutes = [];

    /**
     * @param string      $route
     * @param string      $method
     * @param bool        $expected
     * @param string|null $controller
     * @param string|null $action
     * @param array|null  $params
     */
    public function assertRoute(
        $route,
        $method,
        $expected,
        $controller = null,
        $action = null,
        array $params = null
    ) {
        // GIVEN
        $di = $this->getDI();
        /** @var \Phalcon\Mvc\Router $router */
        $router = $di->getShared(Services::ROUTER);

        $base = $di->getShared(Services::CONFIG)->application->baseUri;

        $route = preg_replace('#^/(.+)#', '$1', $route);

        $uri = $base . $route;

        // WHEN
        $_SERVER['REQUEST_METHOD'] = $method;
        $router->handle($uri);

        // THEN
        $this->assertEquals($expected, $router->wasMatched());

        if ($expected && $router->wasMatched()) {
            $matchedRoute = $router->getMatchedRoute();

            self::$testedRoutes[$matchedRoute->getPattern()] = $matchedRoute;
        }

        $controls = [
            'Controller' => $controller,
            'Action'     => $action
        ];

        foreach ($controls as $key => $value) {
            $key = 'get' . $key . 'Name';

            if (!$expected || ($expected && !is_null($value))) {
                $this->assertEquals($value, $router->$key());
            } elseif ($expected) {
                $this->assertTrue(is_string($router->$key()));
            }
        }

        $routeParams = $router->getParams();
        if ($expected && $router->wasMatched() && $params) {
            foreach ($params as $key => $value) {
                $this->assertArrayHasKey($key, $routeParams);
                $this->assertEquals($value, $routeParams[$key]);
            }
            foreach ($routeParams as $key => $value) {
                $this->assertArrayHasKey($key, $params);
                $this->assertEquals($value, $params[$key]);
            }
        }
    }
}
