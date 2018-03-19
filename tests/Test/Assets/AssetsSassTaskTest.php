<?php

namespace Test\Assets;

use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Assets\SassCompiler;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Debug\Reflexion;
use Neutrino\Foundation\Cli\Tasks\AssetsSassTask;
use Test\TestCase\TestCase;

class AssetsSassTaskTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function testGetSourcemap()
    {
        $task = new AssetsSassTask();

        Reflexion::set($task, 'options', ['sourcemap' => 'none']);
        $this->assertEquals('none', Reflexion::invoke($task, 'getSourcemap'));

        Reflexion::set($task, 'options', []);
        $this->setConfig(['assets' => ['sass' => ['options' => ['sourcemap' => 'with']]]]);
        $this->assertEquals('with', Reflexion::invoke($task, 'getSourcemap'));
    }

    public function testGetSassStyle()
    {
        $task = new AssetsSassTask();

        Reflexion::set($task, 'options', ['compress' => null]);
        $this->assertEquals('compressed', Reflexion::invoke($task, 'getSassStyle'));

        Reflexion::set($task, 'options', ['nested' => null]);
        $this->assertEquals('nested', Reflexion::invoke($task, 'getSassStyle'));

        Reflexion::set($task, 'options', ['compact' => null]);
        $this->assertEquals('compact', Reflexion::invoke($task, 'getSassStyle'));

        Reflexion::set($task, 'options', ['expanded' => null]);
        $this->assertEquals('expanded', Reflexion::invoke($task, 'getSassStyle'));

        Reflexion::set($task, 'options', ['output' => 'compressed']);
        $this->assertEquals('compressed', Reflexion::invoke($task, 'getSassStyle'));
        Reflexion::set($task, 'options', ['output' => 'nested']);
        $this->assertEquals('nested', Reflexion::invoke($task, 'getSassStyle'));
        Reflexion::set($task, 'options', ['output' => 'compact']);
        $this->assertEquals('compact', Reflexion::invoke($task, 'getSassStyle'));
        Reflexion::set($task, 'options', ['output' => 'expanded']);
        $this->assertEquals('expanded', Reflexion::invoke($task, 'getSassStyle'));

        Reflexion::set($task, 'options', []);
        $this->setConfig(['assets' => ['sass' => ['options' => ['style' => 'with']]]]);
        $this->assertEquals('with', Reflexion::invoke($task, 'getSassStyle'));
    }

    public function testMainActionWrongParam()
    {
        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);
        $output->expects($this->exactly(6))
            ->method('error')
            ->withConsecutive(
                ['                                                 '],
                ['  You pass {src} option, without {dest} option.  '],
                ['                                                 '],
                ['                                                 '],
                ['  You pass {dest} option, without {src} option.  '],
                ['                                                 ']
            );
        $task = new AssetsSassTask();

        Reflexion::set($task, 'options', ['src' => 'src']);
        $task->mainAction();

        Reflexion::set($task, 'options', ['dest' => 'dest']);
        $task->mainAction();
    }

    public function testMainActionWithParams()
    {
        $task = new AssetsSassTask();

        Reflexion::set($task, 'options', ['src' => 'src', 'dest' => 'dest', 'compress' => null, 'sourcemap' => 'none']);
        $this->assertCompile('src', 'dest', ['style' => 'compressed', 'sourcemap' => 'none']);
        $task->mainAction();
    }

    public function testMainAction()
    {
        $task = new AssetsSassTask();

        $this->setConfig(['assets' => ['sass' => [
            'files' => [
                'src' => 'dest'
            ],
            'options' => ['style' => 'compressed', 'sourcemap' => 'none']
        ]]]);
        $this->assertCompile('src', 'dest', ['style' => 'compressed', 'sourcemap' => 'none']);
        $task->mainAction();
    }

    public function testCompilationFail()
    {
        $task = new AssetsSassTask();

        Reflexion::set($task, 'options', ['src' => 'src', 'dest' => 'dest', 'compress' => null, 'sourcemap' => 'none']);
        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);
        $output
            ->expects($this->exactly(3))
            ->method('notice')
            ->withConsecutive(['Compiling : '], ["\tsrc  : src"], ["\tdest : dest"]);
        $output
            ->expects($this->never())
            ->method('info');
        $output->expects($this->exactly(3))
            ->method('error')
            ->withConsecutive(
                ['                    '],
                ['  compilation fail  '],
                ['                    ']
            );

        $sass = $this->mockService(SassCompiler::class, SassCompiler::class, true);
        $sass->expects($this->once())->method('compile')->with([
            'sass_file'   => 'src',
            'output_file' => 'dest',
            'cmd_options' => [
                '--style=compressed',
                '--sourcemap="none"',
            ]
        ])->willThrowException(new \Exception('compilation fail'));

        $task->mainAction();
    }

    public function assertCompile($src, $dest, $options)
    {
        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);
        $output
            ->expects($this->exactly(3))
            ->method('notice')
            ->withConsecutive(['Compiling : '], ["\tsrc  : $src"], ["\tdest : $dest"]);
        $output
            ->expects($this->once())
            ->method('info')->with('Success !');

        $sass = $this->mockService(SassCompiler::class, SassCompiler::class, true);
        $sass->expects($this->once())->method('compile')->with([
            'sass_file'   => $src,
            'output_file' => $dest,
            'cmd_options' => [
                '--style=' . $options['style'] . '',
                '--sourcemap="' . $options['sourcemap'] . '"',
            ]
        ]);
    }
}
