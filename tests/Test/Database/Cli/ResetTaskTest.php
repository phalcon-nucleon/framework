<?php

namespace Test\Database\Cli;

/**
 * Class ResetTaskTest
 *
 * @package Test\Database\Cli
 */
class ResetTaskTest extends DatabaseCliTestCase
{
    public function testMainActionNoStorage()
    {
        $this->migrator
            ->expects($this->once())
            ->method('storageExist')
            ->willReturn(false);

        $this->output
            ->expects($this->once())
            ->method('notice')
            ->with('Migration table not found.');

        $this->dispatchCli('quark migrate:reset');
    }

    public function testMainAction()
    {
        $this->migrator->expects($this->once())->method('storageExist')->willReturn(true);
        $this->migrator->expects($this->once())->method('paths')->willReturn([]);

        $this->migrator->expects($this->once())->method('reset')->with([
            BASE_PATH . '/migrations'
        ]);

        $this->migrator->expects($this->once())->method('getNotes')->willReturn([
            'this is a note'
        ]);

        $this->output
            ->expects($this->once())
            ->method('write')
            ->with('this is a note', true);

        $this->dispatchCli('quark migrate:reset');
    }
}
