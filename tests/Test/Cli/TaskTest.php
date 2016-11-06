<?php

namespace Test\Cli;

use Luxury\Cli\Task;
use Luxury\Constants\Services;
use Luxury\Foundation\Cli\HelperTask;
use Phalcon\Cli\Dispatcher;
use Test\Stub\StubKernelCli;
use Test\Stub\StubTask;
use Test\TestCase\TestCase;

class TaskTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    /**
     * @return Task
     */
    private function stubTask()
    {
        StubTask::$enableConstructor = false;

        return new StubTask();
    }

    public function data()
    {
        return [
            ['h', true, 's', ['h' => true]],
            ['help', true, 'h', ['help' => true]],
            ['h', true, 's', ['help' => 'help', 'h' => true]],
            ['help', 'help', 's', ['help' => 'help', 'h' => true]],
        ];
    }

    /**
     * @dataProvider data
     */
    public function testOptions($shouldHave, $value, $shouldNotHave, $options)
    {
        $this->mockService(Services::DISPATCHER, Dispatcher::class, true)
            ->expects($this->any())
            ->method('getOptions')
            ->willReturn($options);

        $task = $this->stubTask();

        $this->assertTrue($this->invokeMethod($task, 'hasOption', [$shouldHave]));
        $this->assertFalse($this->invokeMethod($task, 'hasOption', [$shouldNotHave]));

        $this->assertEquals($value, $this->invokeMethod($task, 'getOption', [$shouldHave]));

        $this->assertEquals($options, $this->invokeMethod($task, 'getOptions', []));
    }

    /**
     * @dataProvider data
     */
    public function testArgs($shouldHave, $value, $shouldNotHave, $options)
    {
        $this->mockService(Services::DISPATCHER, Dispatcher::class, true)
            ->expects($this->any())
            ->method('getParams')
            ->willReturn($options);

        $task = $this->stubTask();

        $this->assertTrue($this->invokeMethod($task, 'hasArg', [$shouldHave]));
        $this->assertFalse($this->invokeMethod($task, 'hasArg', [$shouldNotHave]));

        $this->assertEquals($value, $this->invokeMethod($task, 'getArg', [$shouldHave]));

        $this->assertEquals($options, $this->invokeMethod($task, 'getArgs', []));
    }

    public function testBeforeExecuteRouteForwardHelper()
    {
        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())
            ->method('getOptions')
            ->willReturn(['h' => true]);

        $dispatcher->expects($this->once())
            ->method('getHandlerClass')
            ->willReturn(Task::class);

        $dispatcher->expects($this->once())
            ->method('getActionName')
            ->willReturn('main');

        $dispatcher->expects($this->once())
            ->method('forward')
            ->with([
                'task'   => HelperTask::class,
                'params' => [
                    'task'   => Task::class,
                    'action' => 'main',
                ]
            ]);

        $task = $this->stubTask();

        $this->assertFalse($task->beforeExecuteRoute());
    }

    public function testBeforeExecuteRouteNotForwardHelper()
    {
        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())
            ->method('getOptions')
            ->willReturn([]);

        $task = $this->stubTask();

        $this->assertTrue($task->beforeExecuteRoute());
    }
}
