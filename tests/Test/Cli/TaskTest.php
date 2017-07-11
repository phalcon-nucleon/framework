<?php

namespace Test\Cli;

use Fake\Kernels\Cli\StubKernelCli;
use Fake\Kernels\Cli\Tasks\StubTask;
use Neutrino\Cli\Output\Writer;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Handler;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer\Cli;
use Neutrino\Error\Writer\View;
use Phalcon\Cli\Dispatcher;
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

    public function dataOutput()
    {
        return [
            ['info', 'test'],
            ['notice', 'test'],
            ['question', 'test'],
            ['warn', 'test'],
            ['error', 'test'],
        ];
    }

    /**
     * @dataProvider dataOutput
     */
    public function testOutput($func, $str)
    {
        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        $mock->expects($this->once())
            ->method($func)
            ->with($str);

        $task = $this->stubTask();

        $task->$func($str);
    }

    public function testLine()
    {
        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        $mock->expects($this->once())
            ->method('write')
            ->with('test', true);

        $task = $this->stubTask();

        $task->line('test');
    }
}