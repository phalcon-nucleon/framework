<?php

namespace Test\Assets;

use Neutrino\Assets\SassCompiler;
use Neutrino\Process\Exception;
use Neutrino\Process\Process;
use Test\TestCase\TestCase;

class SassCompilerTest extends TestCase
{
    /**
     * @expectedException \Neutrino\Assets\Exception\CompilatorException
     * @expectedExceptionMessage sass_file option can't be empty.
     */
    public function testEmpySassFile(){

        $sass = new SassCompiler();

        $sass->compile([]);
    }

    /**
     * @expectedException \Neutrino\Assets\Exception\CompilatorException
     * @expectedExceptionMessage output_file option can't be empty.
     */
    public function testEmpyOutputFile(){

        $sass = new SassCompiler();

        $sass->compile(['sass_file' => __FILE__]);
    }

    /**
     * @expectedException \Neutrino\Assets\Exception\CompilatorException
     * @expectedExceptionMessage Can't open process.
     */
    public function testProcessFailCreate()
    {
        $mock = $this->mockService(Process::class, Process::class, false);

        $mock->expects($this->once())->method('start')->willThrowException(new Exception());

        $sass = new SassCompiler();

        $sass->compile([
            'sass_file' => __FILE__,
            'output_file' => __FILE__
        ]);
    }

    /**
     * @expectedException \Neutrino\Assets\Exception\CompilatorException
     * @expectedExceptionMessage proc ouput
     */
    public function testProcessWithOutput()
    {
        $mock = $this->mockService(Process::class, Process::class, false);

        $mock->expects($this->once())->method('start');
        $mock->expects($this->once())->method('wait');
        $mock->expects($this->once())->method('getOutput')->willReturn('proc ouput');

        $sass = new SassCompiler();

        $sass->compile([
            'sass_file' => __FILE__,
            'output_file' => __FILE__
        ]);
    }

    /**
     * @expectedException \Neutrino\Assets\Exception\CompilatorException
     * @expectedExceptionMessage proc error
     */
    public function testProcessWithError()
    {
        $mock = $this->mockService(Process::class, Process::class, false);

        $mock->expects($this->once())->method('start');
        $mock->expects($this->once())->method('wait');
        $mock->expects($this->once())->method('getOutput')->willReturn('');
        $mock->expects($this->once())->method('getError')->willReturn('proc error');

        $sass = new SassCompiler();

        $sass->compile([
            'sass_file' => __FILE__,
            'output_file' => __FILE__
        ]);
    }

    public function testSucess()
    {
        $mock = $this->mockService(Process::class, Process::class, false);

        $mock->expects($this->once())->method('start');
        $mock->expects($this->once())->method('wait');
        $mock->expects($this->once())->method('getOutput')->willReturn('');
        $mock->expects($this->once())->method('getError')->willReturn('');

        $sass = new SassCompiler();

        $this->assertEquals(true, $sass->compile([
            'sass_file'   => __FILE__,
            'output_file' => __FILE__
        ]));
    }
}
