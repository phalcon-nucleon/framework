<?php

namespace Test\Assets;

use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Assets\ClosureCompiler;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Debug\Reflexion;
use Neutrino\Foundation\Cli\Tasks\AssetsJsTask;
use Test\TestCase\TestCase;

class AssetsJsTaskTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function testExtractExternsErrors()
    {
        $task = new AssetsJsTask();

        $result = [
          ['file' => 'src', 'error' => 'error'],
          ['file' => 'dest', 'error' => 'error'],
          ['file' => 'src', 'error' => 'error'],
          ['file' => 'dest', 'error' => 'error'],
          ['file' => 'src', 'error' => 'error'],
          ['file' => 'dest', 'error' => 'error'],
        ];

        $externs = ['src'];

        $count = Reflexion::invokeArgs($task, 'extractExternsErrors', [&$result, $externs]);

        $this->assertEquals(['src' => 3], $count);
        $this->assertEquals([
          ['file' => 'dest', 'error' => 'error'],
          ['file' => 'dest', 'error' => 'error'],
          ['file' => 'dest', 'error' => 'error'],
        ], $result);
    }

    public function testFormatErrorsOrWarnings()
    {
        $task = new AssetsJsTask();

        $item = [
          't1' => "foo\nbar",
          't2' => "foo, bar"
        ];

        $item = Reflexion::invoke($task, 'formatErrorsOrWarnings', $item);

        $this->assertEquals([
          ['t1', "foo, bar"],
          ['t2', "foo, bar"],
        ], $item);
    }

    public function testOutputErrors()
    {
        $task = new AssetsJsTask();
        Reflexion::set($task, 'options', ['verbose-externs' => true]);

        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);
        $output
          ->expects($this->exactly(6))
          ->method('warn')
          ->withConsecutive(
            [str_pad('', 44, ' ')],
            [str_pad('WARNINGS', 44, ' ', STR_PAD_BOTH)],
            [str_pad('', 44, ' ')],
            ['                         '],
            ['  Internal warnings : 1  '],
            ['                         ']
          );
        $output
          ->expects($this->exactly(4))
          ->method('line')
          ->withConsecutive(
            ['                                    '],
            ['  External warnings : (don\'t care)  '],
            ['  jquery.js : 1                     '],
            ['                                    ']
          );
        $output
          ->expects($this->exactly(5))
          ->method('write')
          ->withConsecutive(
            ['+---------+-----------------------------------+'],
            ['| file    | app.js                            |'],
            ['| type    | ERROR_TYPE                        |'],
            ['| warning | Warning: You did something wrong! |'],
            ['+---------+-----------------------------------+']
          );

        $items = [[
          "file" => "jquery.js",
          "type" => "ERROR_TYPE",
          "warning" => "Warning: You did something wrong!",
        ], [
          "file" => "app.js",
          "type" => "ERROR_TYPE",
          "warning" => "Warning: You did something wrong!",
        ]];

        $options['compile']['externs_url'] = ['jquery.js'];

        Reflexion::invoke($task, 'outputErrors', $items, 'warnings', 'warn', $options);
    }

    public function testMainAction()
    {
        $task = new AssetsJsTask();
        Reflexion::set($task, 'options', ['verbose-externs' => true]);
        $this->setConfig(['assets' => ['js' => ['compile' => ['externs_url' => ['jquery.js']]]]]);

        $closure = $this->mockService(ClosureCompiler::class, ClosureCompiler::class, true);

        $closure
          ->expects($this->once())
          ->method('compile')
          ->willReturn([
            'warnings' => [
              [
                "file" => "jquery.js",
                "type" => "ERROR_TYPE",
                "warning" => "Warning: You did something wrong!",
              ], [
                "file" => "app.js",
                "type" => "ERROR_TYPE",
                "warning" => "Warning: You did something wrong!",
              ]
            ],
            'errors' => [
              [
                "file" => "jquery.js",
                "type" => "ERROR_TYPE",
                "warning" => "Warning: You did something wrong!",
              ], [
                "file" => "app.js",
                "type" => "ERROR_TYPE",
                "warning" => "Warning: You did something wrong!",
              ]
            ],
            'statistics' => [
              "originalSize" => 10,
              "compressedSize" => 3000,
              "compileTime" => 10
            ]
          ]);

        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);
        $output
          ->expects($this->exactly(6))
          ->method('warn');
        $output
          ->expects($this->exactly(8))
          ->method('line');
        $output
          ->expects($this->exactly(18))
          ->method('write');
        $output
          ->expects($this->exactly(3))
          ->method('question');
        $output
          ->expects($this->exactly(1))
          ->method('info');

        $task->mainAction();
    }
}
