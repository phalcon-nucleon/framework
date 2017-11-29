<?php

namespace Test\Database\Cli;

use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;

/**
 * Class InstallTaskTest
 *
 * @package Test\Database\Cli
 */
class InstallTaskTest extends DatabaseCliTestCase
{
    public function testMainTask()
    {
        $this->storage->expects($this->once())->method('createStorage');

        $this->output
            ->expects($this->once())
            ->method('info')
            ->with('Migration table created successfully.');

        $this->dispatchCli('quark migrate:install');
    }
}
