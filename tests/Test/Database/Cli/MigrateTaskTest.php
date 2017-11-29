<?php

namespace Test\Database\Cli;

use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;

/**
 * Class MigrateTaskTest
 *
 * @package Test\Database\Cli
 */
class MigrateTaskTest extends DatabaseCliTestCase
{
    public function testMainAction()
    {
        $this->migrator->expects($this->once())->method('storageExist')->willReturn(true);
        $this->migrator->expects($this->once())->method('paths')->willReturn([]);
        $this->migrator->expects($this->once())->method('run')->with(
            [BASE_PATH . '/migrations'],
            ['step' => 0]
        );
        $this->migrator->expects($this->once())->method('getNotes')->willReturn([
            'this is a note'
        ]);

        $this->output
            ->expects($this->once())
            ->method('write')
            ->with('this is a note', true);

        $this->dispatchCli('quark migrate');
    }
}
