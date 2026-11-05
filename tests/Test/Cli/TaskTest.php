<?php

declare(strict_types=1);

namespace Test\Cli;

use Fake\Kernels\Cli\StubKernelCli;
use Fake\Kernels\Cli\Tasks\StubTask;
use Neutrino\Cli\Output\Writer;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Support\Reflection;
use Neutrino\Foundation\Cli\Kernel;
use Phalcon\Cli\Dispatcher;
use Test\TestCase\TestCase;

class TaskTest extends TestCase
{
    protected static function kernelClassInstance(): string
    {
        return StubKernelCli::class;
    }

    /**
     * @return Task
     */
    private function stubTask()
    {
        return new StubTask();
    }

    public static function data(): array
    {
        return [
            ['h', true, 's', ['h' => true]],
            ['help', true, 'h', ['help' => true]],
            ['h', true, 's', ['help' => 'help', 'h' => true]],
            ['help', 'help', 's', ['help' => 'help', 'h' => true]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('data')]
    public function testOptions($shouldHave, $value, $shouldNotHave, $options): void
    {
        $this->mockService(Services::DISPATCHER, Dispatcher::class, true)
            ->expects($this->any())
            ->method('getOptions')
            ->willReturn($options);

        $task = $this->stubTask();

        $this->assertTrue(Reflection::invoke($task, 'hasOption', $shouldHave));
        $this->assertFalse(Reflection::invoke($task, 'hasOption', $shouldNotHave));

        $this->assertEquals($value, Reflection::invoke($task, 'getOption', $shouldHave));

        $this->assertEquals($options, Reflection::invoke($task, 'getOptions'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('data')]
    public function testArgs($shouldHave, $value, $shouldNotHave, $options): void
    {
        $this->mockService(Services::DISPATCHER, Dispatcher::class, true)
            ->expects($this->any())
            ->method('getParams')
            ->willReturn($options);

        $task = $this->stubTask();

        $this->assertTrue(Reflection::invoke($task, 'hasArg', $shouldHave));
        $this->assertFalse(Reflection::invoke($task, 'hasArg', $shouldNotHave));

        $this->assertEquals($value, Reflection::invoke($task, 'getArg', $shouldHave));

        $this->assertEquals($options, Reflection::invoke($task, 'getArgs'));
    }

    public static function dataOutput(): array
    {
        return [
            ['info', 'test'],
            ['notice', 'test'],
            ['question', 'test'],
            ['warn', 'test'],
            ['error', 'test'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataOutput')]
    public function testOutput($func, $str): void
    {
        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        $mock->expects($this->once())
            ->method($func)
            ->with($str);

        $task = $this->stubTask();

        $task->$func($str);
    }

    public function testLine(): void
    {
        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        $mock->expects($this->once())
            ->method('write')
            ->with('test', true);

        $task = $this->stubTask();

        $task->line('test');
    }

    public function testCallTask(): void
    {
        $mock = $this->createMock(Kernel::class);
        $mock->expects($this->once())
            ->method('handle')
            ->with([
                'task'   => 'test',
                'action' => 'act',
                'arg',
                '-tOpt',
                '--strOpt=val',
            ]);

        $this->mockService(Services::APP, $mock);
        $task = $this->stubTask();

        $task->callTask('test', 'act', ['arg'], ['tOpt' => true, 'strOpt' => 'val']);
    }
}
