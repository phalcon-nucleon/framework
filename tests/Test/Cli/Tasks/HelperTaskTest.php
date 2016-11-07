<?php

namespace Test\Cli\Tasks;

use Luxury\Constants\Services;
use Luxury\Foundation\Cli\HelperTask;
use Luxury\Foundation\Cli\ListTask;
use Luxury\Foundation\Cli\OptimizeTask;
use Phalcon\Cli\Dispatcher;
use Phalcon\Cli\Router\Route;
use Phalcon\Events\Manager;
use Test\Stub\StubKernelCli;
use Test\TestCase\TestCase;

class HelperTaskTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function dataResolveRoute()
    {
        return [
            [new Route('list', ['task' => ListTask::class, 'action' => null]), ListTask::class, 'main'],
            [new Route('optimize', ['task' => OptimizeTask::class, 'action' => null]), OptimizeTask::class, 'main']
        ];
    }

    /**
     * @dataProvider dataResolveRoute
     */
    public function testResolveRoute($expected, $class, $action)
    {
        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');

        $task = new HelperTask();

        /** @var Route $route */
        $route = $this->invokeMethod($task, 'resolveRoute', [$class, $action]);

        $this->assertEquals($expected->getPattern(), $route->getPattern());
        $this->assertEquals($expected->getPaths(), $route->getPaths());
    }
}
