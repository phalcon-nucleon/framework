<?php

namespace Test\Assert;

use Luxury\Test\RoutesTestCase;
use Phalcon\Http\Request\Method;
use Phalcon\Mvc\Router\Route;
use Test\Stub\StubController;
use Test\Stub\StubKernelHttp;
use Test\TestCase\TestCase;
use Test\TestCase\TraitTestCase;
use Test\TestCase\UseCaches;

class RoutesTestCaseTest extends TestCase
{
    use TraitTestCase;

    /**
     * @return RoutesTestCase
     */
    public function getStub()
    {
        return new class extends RoutesTestCase
        {
            use TraitTestCase;

            /**
             * @return array[]
             */
            protected function routes(): array
            {
                return [
                    $this->formatDataRoute('/', 'GET', true),
                    $this->formatDataRoute('/', 'POST', false),
                    $this->formatDataRoute('/something/:int', 'GET', true, 'index', StubController::class, [1])
                ];
            }

            public function assertRoute(
                $route,
                $method,
                $expected,
                $controller = null,
                $action = null,
                array $params = null
            )
            {
                static::$testedRoutes[] = [$route, $method, $expected, $controller, $action, $params];
            }
        };
    }

    public function testRouteProvider()
    {
        $routesTestCase = $this->getStub();

        $expecteds = [
            'GET-/-true'               => ['/', 'GET', true, null, null, null],
            'POST-/-false'             => ['/', 'POST', false, null, null, null],
            'GET-/something/:int-true' => ['/something/:int', 'GET', true, 'index', StubController::class, [1]],
        ];

        $routes = $routesTestCase->routesProvider();

        $routesKeys = array_keys($routes);
        $routesValues = array_values($routes);

        $i = 0;
        foreach ($expecteds as $key => $expected) {
            $this->assertStringStartsWith($key, $routesKeys[$i]);
            $this->assertEquals($expected, $routesValues[$i]);

            $i++;
        }
    }

    public function testGetRoutes()
    {
        $routesTestCase = $this->getStub();

        $expectedRoutes = [
            '/'                              => [Route::class],
            '/return'                        => [Route::class],
            '/redirect'                      => [Route::class],
            '/parameted/([\w_-]+)(?:/:int)?' => [Route::class],
            '/forwarded'                     => [Route::class],
        ];

        $appRoutes = $routesTestCase->getRoutes();

        foreach ($expectedRoutes as $route => $expectedRoute) {
            $this->assertArrayHasKey($route, $appRoutes);
            $this->assertInstanceOf($expectedRoute[0], $appRoutes[$route][0]);
        }
    }

    public function testTestRoutes()
    {
        $routesTestCase = $this->getStub();

        $routesTestCase->testRoutes('', Method::GET, true);
        $routesTestCase->testRoutes('', Method::GET, true, 'Stub', 'index');
        $routesTestCase->testRoutes('/parameted/param_1', Method::GET, true, 'Stub', 'index', ['tags' => 'param_1']);

        $this->assertEquals([
            ['', Method::GET, true, null, null, null],
            ['', Method::GET, true, 'Stub', 'index', null],
            ['/parameted/param_1', Method::GET, true, 'Stub', 'index', ['tags' => 'param_1']],
        ], $this->valueProperty($routesTestCase, 'testedRoutes', RoutesTestCase::class));
    }
}
