<?php

namespace Test\Middleware;

use Luxury\Constants\Services;
use Luxury\Foundation\Middleware\Controller;
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
        return new class extends Controller
        {

        };
    }

    public function testFilter()
    {
        $controller = $this->getStubControllerMiddleware();

        $this->assertEquals($controller, $controller->only(null));
        $this->assertEquals($controller, $controller->except(null));

        $this->assertEquals(
            [],
            $this->valueProperty($controller, 'filter', 'Luxury\Foundation\Middleware\Controller')
        );

        $this->assertEquals($controller, $controller->only([]));
        $this->assertEquals($controller, $controller->except([]));

        $this->assertEquals([
            'only'   => [],
            'except' => []
        ], $this->valueProperty($controller, 'filter', 'Luxury\Foundation\Middleware\Controller'));

        $this->assertEquals($controller, $controller->only(['test']));
        $this->assertEquals($controller, $controller->except(['test']));

        $this->assertEquals([
            'only'   => ['test'],
            'except' => ['test']
        ], $this->valueProperty($controller, 'filter', 'Luxury\Foundation\Middleware\Controller'));

        $this->assertEquals($controller, $controller->only(null));
        $this->assertEquals($controller, $controller->except(null));

        $this->assertEquals([
            'only'   => ['test'],
            'except' => ['test']
        ], $this->valueProperty($controller, 'filter', 'Luxury\Foundation\Middleware\Controller'));

        $this->assertEquals($controller, $controller->only([]));
        $this->assertEquals($controller, $controller->except([]));

        $this->assertEquals([
            'only'   => [],
            'except' => []
        ], $this->valueProperty($controller, 'filter', 'Luxury\Foundation\Middleware\Controller'));
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
        $dispatcher = $this->getMockBuilder(\Phalcon\Mvc\Dispatcher::class)
            ->setMethods(['getActionName', 'getActionSuffix'])
            ->getMock();
        $dispatcher->expects($this->any())
            ->method('getActionName')
            ->will($this->returnValue($actionName));
        $dispatcher->expects($this->any())->method('getActionSuffix')->will($this->returnValue(''));

        $this->getDI()->remove(Services::DISPATCHER);
        $this->getDI()->setShared(Services::DISPATCHER, $dispatcher);

        $controller = $this->getStubControllerMiddleware();

        $controller->$filterType([$filter]);

        $this->assertEquals($expected, $controller->check());
    }
}
