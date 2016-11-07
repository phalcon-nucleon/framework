<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 07/11/2016
 * Time: 10:53
 */

namespace Test\Stub;


use Luxury\Cli\Output\ConsoleOutput;

class StubConsoleOutput extends ConsoleOutput
{
    public $out;

    public function write($message, $newline)
    {
        if (!$this->quiet)
            $this->out .= $message . ($newline ? PHP_EOL : '');
    }
}