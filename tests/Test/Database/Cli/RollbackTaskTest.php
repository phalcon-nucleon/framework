<?php

namespace Test\Database\Cli;

/**
 * Class RollbackTaskTest
 *
 * @package Test\Database\Cli
 */
class RollbackTaskTest extends DatabaseCliTestCase
{
    public function testMainAction()
    {
        $this->migrator->expects($this->once())->method('paths')->willReturn([]);
        $this->migrator->expects($this->once())->method('rollback')->with(
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

        $this->dispatchCli('quark migrate:rollback');
    }
}
