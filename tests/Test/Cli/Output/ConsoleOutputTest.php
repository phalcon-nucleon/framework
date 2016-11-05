<?php

namespace Test\Cli\Output;

use Luxury\Cli\Output\ConsoleOutput;

class ConsoleOutputTest extends \PHPUnit_Framework_TestCase
{

    private $stream;

    private function openStream()
    {
        ob_start();
        if ($this->stream == null) {
            $this->stream = fopen('php://stdout', 'r');
        }
    }

    private function output()
    {
        return new ConsoleOutput();
    }

    public function setUp()
    {
        parent::setUp();

        putenv('TERM=xterm');
    }

    public function tearDown()
    {
        parent::tearDown();

        if ($this->stream) {
            fclose($this->stream);
        }
    }

    public function dataApply()
    {
        return [
            ["test", ['test']],
            ["\033[33mtest\033[39m", ['test', 'yellow']],
            ["\033[33;47mtest\033[39;49m", ['test', 'yellow', 'white']],
            ["\033[33;47;1mtest\033[39;49;22m", ['test', 'yellow', 'white', ['bold']]],
            ["\033[33;47;1;4mtest\033[39;49;22;24m", ['test', 'yellow', 'white', ['bold', 'underscore']]],
        ];
    }

    /**
     * @dataProvider dataApply
     */
    public function testApply($expected, $params)
    {
        $this->assertEquals($expected, $this->output()->apply(...$params));
    }

    public function dataColorisedFunctions()
    {
        return [
            ["\033[32mtest\033[39m", 'info'],
            ["\033[33mtest\033[39m", 'notice'],
            ["\033[33;7mtest\033[39;27m", 'warn'],
            ["\033[30;41mtest\033[39;49m", 'error'],
            ["\033[30;46mtest\033[39;49m", 'question'],
        ];
    }

    /**
     * @dataProvider dataColorisedFunctions
     */
    public function testColorisedFunctions($expected, $func)
    {
        $this->assertEquals($expected, $this->output()->$func('test'));
    }
}
