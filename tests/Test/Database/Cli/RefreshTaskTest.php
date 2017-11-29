<?php

namespace Test\Database\Cli;

/**
 * Class RefreshTaskTest
 *
 * @package Test\Database\Cli
 */
class RefreshTaskTest extends DatabaseCliTestCase
{
    public function testMainActionNoStep()
    {
        $this->migrator->expects($this->exactly(2))->method('storageExist')->willReturn(true);
        $this->migrator->expects($this->exactly(2))->method('paths')->willReturn([]);
        $this->migrator->expects($this->exactly(2))->method('getNotes')->willReturn([]);

        $this->migrator->expects($this->once())->method('reset')->with([BASE_PATH . '/migrations']);

        $this->migrator->expects($this->once())->method('run')->with(
            [BASE_PATH . '/migrations'],
            ['step' => 0]
        );

        $this->dispatchCli('quark migrate:refresh');
    }

    public function testMainActionWithStep()
    {
        $this->migrator->expects($this->exactly(1))->method('storageExist')->willReturn(true);
        $this->migrator->expects($this->exactly(2))->method('paths')->willReturn([]);
        $this->migrator->expects($this->exactly(2))->method('getNotes')->willReturn([]);

        $this->migrator->expects($this->once())->method('rollback')->with([BASE_PATH . '/migrations']);

        $this->migrator->expects($this->once())->method('run')->with(
            [BASE_PATH . '/migrations'],
            ['step' => 3]
        );

        $this->dispatchCli('quark migrate:refresh --step=3');
    }
}
