<?php

namespace Test\Cli\Tasks;

use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Test\TestCase\TestCase;

class DefaultTaskTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function testMainActionOne()
    {
        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        $output
          ->expects($this->exactly(5))
          ->method('error')
          ->withConsecutive(
            ['                                   '],
            ['  Command "server:rnu" not found.  '],
            ['  Did you mean this ?              '],
            ['    server:run                     '],
            ['                                   ']
          );

        $this->dispatchCli('quark server:rnu');
    }

    public function testMainActionTwo()
    {
        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        $output
          ->expects($this->exactly(11))
          ->method('error')
          ->withConsecutive(
            ['                                 '],
            ['  Command "migrate:" not found.  '],
            ['  Did you mean one of theses ?   '],
            ['    migrate                      '],
            ['    migrate:fresh                '],
            ['    migrate:install              '],
            ['    migrate:refresh              '],
            ['    migrate:reset                '],
            ['    migrate:rollback             '],
            ['    migrate:status               '],
            ['                                 ']
          );

        $this->dispatchCli('quark migrate:');
    }
}
