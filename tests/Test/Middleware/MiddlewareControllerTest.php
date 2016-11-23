<?php

namespace Test\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Foundation\Middleware\Controller;
use Test\Stub\StubController;
use Test\TestCase\TestCase;

/**
 * Class MiddlewareControllerTest
 *
 * @package Test\Middleware
 */
class MiddlewareControllerTest extends TestCase
{
    /**
     * @return Controller
     */
    public function getStubControllerMiddleware()
    {
        return new StubMiddlewareController(StubController::class);
    }

    public function testFilter()
    {
        $controller = $this->getStubControllerMiddleware();

        $this->assertEquals($controller, $controller->only(null));
        $this->assertEquals($controller, $controller->except(null));

        $this->assertEquals(
            [],
            $this->getValueProperty($controller, 'filter', \Neutrino\Foundation\Middleware\Controller::class)
        );

        $this->assertEquals($controller, $controller->only([]));
        $this->assertEquals($controller, $controller->except([]));

        $this->assertEquals([
            'only'   => [],
            'except' => []
        ], $this->getValueProperty($controller, 'filter', \Neutrino\Foundation\Middleware\Controller::class));

        $this->assertEquals($controller, $controller->only(['test']));
        $this->assertEquals($controller, $controller->except(['test']));

        $this->assertEquals([
            'only'   => ['test'],
            'except' => ['test']
        ], $this->getValueProperty($controller, 'filter', \Neutrino\Foundation\Middleware\Controller::class));

        $this->assertEquals($controller, $controller->only(null));
        $this->assertEquals($controller, $controller->except(null));

        $this->assertEquals([
            'only'   => ['test'],
            'except' => ['test']
        ], $this->getValueProperty($controller, 'filter', \Neutrino\Foundation\Middleware\Controller::class));

        $this->assertEquals($controller, $controller->only([]));
        $this->assertEquals($controller, $controller->except([]));

        $this->assertEquals([
            'only'   => [],
            'except' => []
        ], $this->getValueProperty($controller, 'filter', \Neutrino\Foundation\Middleware\Controller::class));
    }

    public function dataCheck()
    {
        return [
            ['only', 'test', 'test', true],
            ['except', 'test', 'test', false],
            ['only', 'testing', 'test', false],
            ['except', 'testing', 'test', true],
        ];
    }

    /**
     * @dataProvider dataCheck
     *
     * @param $filterType
     * @param $filter
     * @param $actionName
     * @param $expected
     */
    public function testCheck($filterType, $filter, $actionName, $expected)
    {
        $dispatcher = $this->mockService(Services::DISPATCHER, \Phalcon\Mvc\Dispatcher::class, true);

        $dispatcher->expects($this->any())
            ->method('getActionName')
            ->will($this->returnValue($actionName));
        $dispatcher->expects($this->any())
            ->method('getActionSuffix')
            ->will($this->returnValue(''));
        $dispatcher->expects($this->any())
            ->method('getHandlerClass')
            ->will($this->returnValue(StubController::class));

        $controller = $this->getStubControllerMiddleware();

        $controller->$filterType([$filter]);

        $this->assertEquals($expected, $controller->check());
    }
}

class StubMiddlewareController extends Controller
{

}