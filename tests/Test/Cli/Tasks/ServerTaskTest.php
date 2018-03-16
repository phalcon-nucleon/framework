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

    public function testIp()
    {
        $server = new ServerTask;

        $this->assertEquals('127.0.0.1', Reflexion::invoke($server, 'getIp'));

        Reflexion::set($server, 'options', ['ip' => true]);

        try{
            Reflexion::invoke($server, 'getIp');
        } catch (\Exception $e){}

        $this->assertTrue(isset($e));
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertEquals('IP can\'t be empty', $e->getMessage());

        Reflexion::set($server, 'options', ['ip' => '123456789']);

        try{
            Reflexion::invoke($server, 'getIp');
        } catch (\Exception $e){}

        $this->assertTrue(isset($e));
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertEquals('[123456789] is not a valid ip', $e->getMessage());
    }

    public function testPort()
    {
        try {
            $p1 = new Process(PHP_BINARY . ' -S 127.0.0.1:8000');
            $p2 = new Process(PHP_BINARY . ' -S 127.0.0.1:8001');

            $p1->start();
            $p2->start();

            sleep(1);

            $server = new ServerTask;

            $this->assertEquals(8002, Reflexion::invoke($server, 'acquirePort', '127.0.0.1'));
            $this->assertEquals(8002, Reflexion::invoke($server, 'getPort', '127.0.0.1'));

            Reflexion::set($server, 'options', ['port' => true]);

            try{
                Reflexion::invoke($server, 'getPort', '127.0.0.1');
            } catch (\Exception $e){}

            $this->assertTrue(isset($e));
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertEquals('Port can\'t be empty', $e->getMessage());

            Reflexion::set($server, 'options', ['port' => 8000]);

            try{
                Reflexion::invoke($server, 'getPort', '127.0.0.1');
            } catch (\Exception $e){}

            $this->assertTrue(isset($e));
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertEquals('Port [8000] on ip [127.0.0.1] is already used.', $e->getMessage());
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
