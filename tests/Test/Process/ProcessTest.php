<?php

namespace Test\Process;

use Neutrino\Process\Process;
use Neutrino\Process\Timeout;
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
        $this->assertContains("int(" . PHP_VERSION_ID . ")\n", $process->getOutput());
        $this->assertEquals('', $process->getError());

        $process->wait();

        $this->assertFalse($process->isRunning());
        $this->assertContains("int(" . PHP_VERSION_ID . ")\nend", $process->getOutput());
        $this->assertEquals('', $process->getError());

        $process->close();
    }

    public function testExec(){
        $process = new Process(PHP_BINARY . ' -r "ob_start();var_dump(PHP_VERSION_ID);flush();ob_flush();sleep(3);echo \'end\';"', __DIR__);

        $process->exec();

        $this->assertFalse($process->isRunning());
        $this->assertNotEmpty($process->pid());
        $this->assertContains("int(" . PHP_VERSION_ID . ")\nend", $process->getOutput());
        $this->assertEquals('', $process->getError());
    }

    public function testExecTimeout()
    {
        $process = new Process(PHP_BINARY . ' -r "ob_start();var_dump(PHP_VERSION_ID);flush();ob_flush();sleep(3);echo \'end\';"', __DIR__);

        $total = INF;
        $start = microtime(true);
        try{
            $process->exec(100);
        } catch (\Exception $e){
            $total = microtime(true) - $start;
            $this->assertInstanceOf(Timeout::class, $e);
        }

        $this->assertFalse($process->isRunning());
        $this->assertNotEmpty($process->pid());
        $this->assertLessThan(1, $total);
    }


    public function testWatch()
    {
        $process = new Process(PHP_BINARY . ' -r "ob_start();var_dump(123);ob_end_flush();sleep(1);ob_start();var_dump(456);ob_end_flush();sleep(1);ob_start();echo \'end\';ob_end_flush();"', __DIR__);

        try {
            $readed = [];
            $errors = [];

            $process
                ->start()
                ->watch(function ($read, $error) use (&$readed, &$errors) {
                    $readed[] = $read;
                    $errors[] = $error;
                }, null, 1);

            $this->assertEquals([
                'int(123)' . "\n",
                'int(456)' . "\n",
                'end',
            ], $readed);
            $this->assertEquals(['', '', ''], $errors);

            $this->assertFalse($process->isRunning());
            $this->assertNotEmpty($process->pid());
        } finally {
            $process->close();
        }
    }

    /**
     * @expectedException \Neutrino\Process\Exception
     */
    public function testFailStart()
    {
        $this->markTestSkipped();

        $process = new Process('this_is_not_a_valid_program');

        $process->start();
    }
}
