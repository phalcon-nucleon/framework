<?php

namespace Test\Cli\Tasks;

use Neutrino\Cli\Output\ConsoleOutput;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Cli\Tasks\ListTask;
use Neutrino\Foundation\Cli\Tasks\OptimizeTask;
use Neutrino\Foundation\Cli\Tasks\RouteListTask;
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
                'description' => 'Optimize the autoloader.',
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
                'description' => 'Optimize the autoloader.',
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
            'write' => ['exactly' => 7, 'consecutive' => [
                //['Available Commands :'],
                [' '.Decorate::info('help ( .*)*').'                                    ', true],
                [' '.Decorate::info('list').'            List all commands available.   ', true],
                [' '.Decorate::info('optimize').'        Optimize the autoloader.       ', true],
                [' '.Decorate::info('clear-compiled').'  Clear compilation.             ', true],
                //['config', true],
                [' '.Decorate::info('config:cache').'    Clear all compiled view files. ', true],
                //['route', true],
                [' '.Decorate::info('route:list').'      List all routes.               ', true],
                //['view', true],
                [' '.Decorate::info('view:clear').'      Clear all compiled view files. ', true],
            ]],
            'notice' => ['exactly' => 4, 'consecutive' => [
                [Decorate::notice('Available Commands :')],
                [Decorate::notice('config')],
                [Decorate::notice('route')],
                [Decorate::notice('view')],
            ]]
        ];
//config
//        config:cache                       Clear all compiled view files.

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
