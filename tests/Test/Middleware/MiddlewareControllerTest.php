<?php

declare(strict_types=1);

namespace Test\Middleware;

use Fake\Kernels\Http\Controllers\StubController;
use Neutrino\Constants\Services;
use Neutrino\Support\Reflection;
use Neutrino\Foundation\Middleware\Controller;
use Test\TestCase\TestCase;

/**
 * Class MiddlewareControllerTest
 *
 * @package Test\Middleware
 */
final class MiddlewareControllerTest extends TestCase
{
    /**
     * @return Controller
     */
    public function getStubControllerMiddleware()
    {
        return new StubMiddlewareController(StubController::class);
    }

    public function testFilter(): void
    {
        $controller = $this->getStubControllerMiddleware();

        $this->assertEquals($controller, $controller->only(null));
        $this->assertEquals($controller, $controller->except(null));

        $this->assertEquals(
            [],
            Reflection::get($controller, 'filter'),
        );

        $this->assertEquals($controller, $controller->only([]));
        $this->assertEquals($controller, $controller->except([]));

        $this->assertEquals([
            'only'   => [],
            'except' => [],
        ], Reflection::get($controller, 'filter'));

        $this->assertEquals($controller, $controller->only(['test']));
        $this->assertEquals($controller, $controller->except(['test']));

        $this->assertEquals([
            'only'   => ['test' => true],
            'except' => ['test' => true],
        ], Reflection::get($controller, 'filter'));

        $this->assertEquals($controller, $controller->only(null));
        $this->assertEquals($controller, $controller->except(null));

        $this->assertEquals([
            'only'   => ['test' => true],
            'except' => ['test' => true],
        ], Reflection::get($controller, 'filter'));

        $this->assertEquals($controller, $controller->only([]));
        $this->assertEquals($controller, $controller->except([]));

        $this->assertEquals([
            'only'   => [],
            'except' => [],
        ], Reflection::get($controller, 'filter'));
    }

    public function testFiltersAddUp(): void
    {
        $controller = $this->getStubControllerMiddleware();

        $controller->only(['index'])->only(['show'])->except(['a'])->except(['b']);

        $this->assertSame([
            'only'   => ['index' => true, 'show' => true],
            'except' => ['a' => true, 'b' => true],
        ], Reflection::get($controller, 'filter'));

        $controller->only([]);
        $this->assertSame([], Reflection::get($controller, 'filter')['only']);
    }

    public static function dataCheck(): array
    {
        return [
            ['only', 'test', 'test', true],
            ['except', 'test', 'test', false],
            ['only', 'testing', 'test', false],
            ['except', 'testing', 'test', true],
        ];
    }

    /**
     *
     * @param $filterType
     * @param $filter
     * @param $actionName
     * @param $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataCheck')]
    public function testCheck($filterType, $filter, $actionName, $expected): void
    {
        $dispatcher = $this->mockService(Services::DISPATCHER, \Phalcon\Mvc\Dispatcher::class, true);

        $dispatcher->expects($this->any())
            ->method('getActionName')
            ->willReturn($actionName);
        $dispatcher->expects($this->any())
            ->method('getActionSuffix')
            ->willReturn('');
        $dispatcher->expects($this->any())
            ->method('getHandlerClass')
            ->willReturn(StubController::class);

        $controller = $this->getStubControllerMiddleware();

        $controller->$filterType([$filter]);

        $this->assertEquals($expected, $controller->check());
    }
}

class StubMiddlewareController extends Controller {}
