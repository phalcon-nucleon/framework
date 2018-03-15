<?php

namespace Test\Process;

use Neutrino\Process\Process;
use Test\TestCase\TestCase;

class ProcessTest extends TestCase
{
    public function testStartWait()
    {
        $process = new Process(PHP_BINARY . ' -r "ob_start();var_dump(PHP_VERSION_ID);flush();ob_flush();sleep(3);echo \'end\';"', __DIR__);

        $process->start();

        $this->assertTrue($process->isRunning());
        $this->assertEquals('', $process->getOutput());
        $this->assertEquals('', $process->getError());
        $this->assertNotEmpty($process->pid());

        $process->wait(1000);

        $this->assertTrue($process->isRunning());
        $this->assertEquals("Command line code:1:\nint(" . PHP_VERSION_ID . ")\n", $process->getOutput());
        $this->assertEquals('', $process->getError());

        $process->wait();

        $this->assertFalse($process->isRunning());
        $this->assertEquals("Command line code:1:\nint(" . PHP_VERSION_ID . ")\nend", $process->getOutput());
        $this->assertEquals('', $process->getError());

        $process->close();
    }

    public function testExec(){
        $process = new Process(PHP_BINARY . ' -r "ob_start();var_dump(PHP_VERSION_ID);flush();ob_flush();sleep(3);echo \'end\';"', __DIR__);

        $process->exec();

        $this->assertFalse($process->isRunning());
        $this->assertNotEmpty($process->pid());
        $this->assertEquals("Command line code:1:\nint(" . PHP_VERSION_ID . ")\nend", $process->getOutput());
        $this->assertEquals('', $process->getError());
    }
}
