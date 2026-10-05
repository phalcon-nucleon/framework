<?php

declare(strict_types=1);

namespace Test\Assert;

use Fake\Kernels\Http\Controllers\StubController;
use Fake\Test\StubRouteTestCase;
use Phalcon\Mvc\Router\Route;
use Test\TestCase\TestCase;

final class RoutesTestCaseTest extends TestCase
{
    public function testRoutesProvider(): void
    {
        $this->assertSame([
            'GET-/-true-0'               => ['/', 'GET', true, null, null, null],
            'POST-/-false-1'             => ['/', 'POST', false, null, null, null],
            'GET-/something/:int-true-2' => ['/something/:int', 'GET', true, 'index', StubController::class, ['id' => 1]],
        ], StubRouteTestCase::routesProvider());
    }

    public function testFormatDataRoute(): void
    {
        $this->assertSame(['/a', 'GET', true, 'c', 'a', ['x' => 1]], StubRouteTestCase::formatDataRoute('/a', 'GET', true, 'c', 'a', ['x' => 1]));
        $this->assertSame(['/a', 'GET', false, null, null, null], StubRouteTestCase::formatDataRoute('/a', 'GET', false));
    }

    public function testGetApplicationRoutes(): void
    {
        $routes = StubRouteTestCase::getApplicationRoutes();

        foreach (['/get', '/post', '/u/:int', '/get-head', '/back/:controller/:action'] as $pattern) {
            $this->assertArrayHasKey($pattern, $routes);
            $this->assertInstanceOf(Route::class, $routes[$pattern][0]);
        }
        $this->assertSame(['GET', 'HEAD'], $routes['/get-head'][0]->getHttpMethods());
    }

    public function testTestRoutesCallsAssertRoute(): void
    {
        $routesTestCase = new StubRouteTestCase('testRoutes');

        $routesTestCase->testRoutes('', 'GET', true);
        $routesTestCase->testRoutes('/parameted/param_1', 'GET', true, 'Stub', 'index', ['tags' => 'param_1']);

        $this->assertSame([
            ['', 'GET', true, null, null, null],
            ['/parameted/param_1', 'GET', true, 'Stub', 'index', ['tags' => 'param_1']],
        ], $routesTestCase->assertedRoutes);
    }
}
