<?php

namespace Test\Cli\Output;

use Luxury\Cli\Output\ConsoleOutput;

class ConsoleOutputTest extends \PHPUnit_Framework_TestCase
{
    private function forceColor()
    {
        putenv('TERM=xterm');
    }

    public function testApply()
    {
        $this->forceColor();

        $output = new ConsoleOutput();

        $this->assertEquals("\033[33mt\033[39m", $output->apply('t', 'yellow'));
    }
}
