<?php

namespace Test\Database\Cli;

use Neutrino\Constants\Services;
use Neutrino\Database\Schema\DialectInterface;
use Phalcon\Db\Adapter;

/**
 * Class FreshTaskTest
 *
 * @package Test\Database\Cli
 */
class FreshTaskTest extends DatabaseCliTestCase
{
    public function testMainAction()
    {
        $db = $this->mockService(Services::DB, Adapter::class, true);
        $db
            ->expects($this->exactly(2))
            ->method('execute');

        $db
            ->expects($this->once())
            ->method('getDialect')
            ->willReturn($this->createMock(DialectInterface::class));
        $db
            ->expects($this->once())
            ->method('listTables')
            ->willReturn([]);

        $this->output->expects($this->once())->method('info')->with('Dropped all tables successfully.');

        $this->migrator->expects($this->once())->method('storageExist')->willReturn(true);
        $this->migrator->expects($this->once())->method('paths')->willReturn([]);
        $this->migrator->expects($this->once())->method('getNotes')->willReturn([]);
        $this->migrator->expects($this->once())->method('run')->with(
            [BASE_PATH . '/migrations'],
            ['step' => 0, 'pretend' => false]
        );

        $this->dispatchCli('quark migrate:fresh');
    }
}
