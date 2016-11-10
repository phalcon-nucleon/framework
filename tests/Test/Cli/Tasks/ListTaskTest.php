<?php

namespace Test\Cli\Tasks;

use Luxury\Cli\Output\ConsoleOutput;
use Luxury\Cli\Output\Decorate;
use Luxury\Constants\Services;
use Luxury\Foundation\Cli\ListTask;
use Luxury\Foundation\Cli\OptimizeTask;
use Luxury\Foundation\Cli\RouteListTask;
use Phalcon\Cli\Dispatcher;
use Phalcon\Cli\Router\Route;
use Phalcon\Events\Manager;
use Test\Stub\StubKernelCli;
use Test\TestCase\TestCase;

class ListTaskTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function dataDescribe()
    {
        return [
            [[
                'description' => 'List all commands available.',
                'cmd'         => Decorate::info('list'),
            ], 'list', ListTask::class, 'mainAction'],
            [[
                'description' => 'List all routes.',
                'cmd'         => Decorate::info('route:list'),
                'options'     => '--no-substitution: Doesn\'t replace matching group by params name',
            ], 'route:list', RouteListTask::class, 'mainAction'],
            [[
                'description' => 'Optimize the loader.',
                'cmd'         => Decorate::info('optimize'),
                'options'     => '-m, --memory: Optimize memory.',
            ], 'optimize', OptimizeTask::class, 'mainAction']
        ];
    }

    /**
     * @dataProvider dataDescribe
     */
    public function testDescribe($expected, $cmd, $class, $action)
    {
        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);

        $task = new ListTask();

        $this->invokeMethod($task, 'describe', [$cmd, $class, $action]);

        $describes = $this->getValueProperty($task, 'describes');

        $this->assertEquals([$expected], $describes);
    }

    public function dataDescribeRoute()
    {
        return [
            [[
                'description' => 'List all commands available.',
                'cmd'         => Decorate::info('list'),
            ], new Route('list', ['task' => ListTask::class])],
            [[
                'description' => 'List all routes.',
                'cmd'         => Decorate::info('route:list'),
                'options'     => '--no-substitution: Doesn\'t replace matching group by params name',
            ], new Route('route:list', ['task' => RouteListTask::class])],
            [[
                'description' => 'Optimize the loader.',
                'cmd'         => Decorate::info('optimize'),
                'options'     => '-m, --memory: Optimize memory.',
            ], new Route('optimize', ['task' => OptimizeTask::class])]
        ];
    }

    /**
     * @dataProvider dataDescribeRoute
     */
    public function testDescribeRoute($expected, $route)
    {
        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');

        $task = new ListTask();

        $this->invokeMethod($task, 'describeRoute', [$route]);

        $describes = $this->getValueProperty($task, 'describes');

        $this->assertEquals([$expected], $describes);
    }

    public function testMainAction()
    {
        $expected = [
            'write' => ['exactly' => 9, 'consecutive' => [
                ['Available Commands :'],
                [' '.Decorate::info('help ( .*)*').'                                    ', true],
                [' '.Decorate::info('list').'            List all commands available.   ', true],
                [' '.Decorate::info('optimize').'        Optimize the loader.           ', true],
                [' '.Decorate::info('clear-compiled').'  Clear compilation.             ', true],
                ['route', true],
                [' '.Decorate::info('route:list').'      List all routes.               ', true],
                ['view', true],
                [' '.Decorate::info('view:clear').'      Clear all compiled view files. ', true],
            ]]
        ];

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($this->createMock(Manager::class));
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');

        $mock = $this->createMock(ConsoleOutput::class);
        foreach ($expected as $func => $params) {
            $method = $mock->expects($this->exactly($params['exactly']))
                ->method($func);

            if (!empty($params['consecutive'])) {
                $method->withConsecutive(...$params['consecutive']);
            }
        }

        $task = new ListTask();

        $this->setValueProperty($task, 'output', $mock);

        $task->mainAction();
    }
}
