<?php

declare(strict_types=1);

namespace Test\Assert;

use Neutrino\Test\Helpers\RoutesTrait;
use PHPUnit\Framework\ExpectationFailedException;
use Test\TestCase\TestCase;

final class RoutesTraitTest extends TestCase
{
    use RoutesTrait;

    public function testAssertRouteOk(): void
    {
        $this->assertRoute('', 'GET', true, 'Stub', 'index');
        $this->assertRoute('', 'GET', true);
        $this->assertRoute('/fail', 'GET', false);
    }

    public function testAssertRouteWithParams(): void
    {
        $this->assertRoute('/parameted/param_1', 'GET', true, 'Stub', 'index', ['tags' => 'param_1']);
        $this->assertRoute('/parameted/param_1/123', 'GET', true, 'Stub', 'index', ['tags' => 'param_1', 'page' => '123']);
    }

    public function testMatchedRoutesAreRecorded(): void
    {
        $this->assertRoute('/redirect', 'GET', true);

        $this->assertArrayHasKey('/redirect', self::$testedRoutes);
    }

    /**
     * @return iterable<string, array{string, string, string, string, array<string, string>|null}>
     */
    public static function wrongRoutes(): iterable
    {
        yield 'method' => ['', 'PUT', 'Stub', 'index', null];
        yield 'controller' => ['', 'GET', 'Wrong', 'index', null];
        yield 'action' => ['', 'GET', 'Stub', 'wrong', null];
        yield 'params' => ['/parameted/1.2.3.4', 'GET', 'Stub', 'index', ['tags' => 'param_1']];
        yield 'missing param' => ['/parameted/abc123/zyx', 'GET', 'Stub', 'index', ['tags' => 'param_1']];
    }

    /**
     * @param array<string, string>|null $params
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('wrongRoutes')]
    public function testAssertRouteFails(string $route, string $method, string $controller, string $action, ?array $params): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->assertRoute($route, $method, true, $controller, $action, $params);
    }

    public function testRequestMethodIsRestored(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $this->assertRoute('', 'GET', true);

        $this->assertSame('OPTIONS', $_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_METHOD']);
    }
}
