<?php

namespace Test\Cli\Tasks;

use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Debug\Reflexion;
use Neutrino\Foundation\Cli\Tasks\ServerTask;
use Neutrino\Process\Exception;
use Neutrino\Process\Process;
use Test\TestCase\TestCase;

class ServerTaskTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function testAcquirePort()
    {
        try {
            $p1 = new Process(PHP_BINARY . ' -S 127.0.0.1:8000');
            $p2 = new Process(PHP_BINARY . ' -S 127.0.0.1:8001');

            $p1->start();
            $p2->start();

            sleep(1);

            $server = new ServerTask;

            $this->assertEquals(8002, Reflexion::invoke($server, 'acquirePort', '127.0.0.1'));
        } finally {
            $p1->close();
            $p2->close();
        }
    }

    public function testServerCantStart()
    {
        $process = $this->mockService(Process::class, Process::class, false);
        $process->expects($this->once())->method('start')->willThrowException(new Exception);

        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class, false);
        $output->expects($this->exactly(3))->method('error');

        $this->dispatchCli('quark server:run');
    }

    public function testServer()
    {
        $process = $this->mockService(Process::class, Process::class, false);
        $process->expects($this->once())->method('start');

        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);
        $output->expects($this->exactly(3))->method('info');
        $output->expects($this->exactly(3))->method('error');

        $this->dispatchCli('quark server:run');
    }
}
