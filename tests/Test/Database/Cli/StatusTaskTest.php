<?php

namespace Test\Database\Cli;

/**
 * Class StatusTaskTest
 *
 * @package Test\Database\Cli
 */
class StatusTaskTest extends DatabaseCliTestCase
{
    public function testMainActionNotInstalled()
    {
        $this->migrator
            ->expects($this->once())
            ->method('storageExist')
            ->willReturn(false);

        $this->output
            ->expects($this->once())
            ->method('error')
            ->with('No migrations found.');

        $this->dispatchCli('quark migrate:status');
    }

    public function testMainActionNoMigrations()
    {
        $this->migrator
            ->expects($this->once())
            ->method('storageExist')
            ->willReturn(true);

        $this->migrator
            ->expects($this->once())
            ->method('paths')
            ->willReturn([]);

        $this->migrator
            ->expects($this->once())
            ->method('getMigrationFiles')
            ->willReturn([]);

        $this->storage
            ->expects($this->once())
            ->method('getRan')
            ->willReturn([]);

        $this->output
            ->expects($this->once())
            ->method('error')
            ->with('No migrations found.');

        $this->dispatchCli('quark migrate:status');
    }

    public function testMainActionWithMigrations()
    {
        $this->migrator
            ->expects($this->once())
            ->method('storageExist')
            ->willReturn(true);

        $this->migrator
            ->expects($this->once())
            ->method('paths')
            ->willReturn([]);

        $this->migrator
            ->expects($this->once())
            ->method('getMigrationFiles')
            ->willReturn([
                BASE_PATH . '/migrations/123_create_users_table.php',
                BASE_PATH . '/migrations/456_create_profiles_table.php',
                BASE_PATH . '/migrations/789_create_roles_table.php',
            ]);

        $this->migrator
            ->expects($this->exactly(3))
            ->method('getMigrationName')
            ->willReturnCallback(function ($path) {
                return str_replace('.php', '', basename($path));
            });

        $this->storage
            ->expects($this->once())
            ->method('getRan')
            ->willReturn([
                '123_create_users_table'
            ]);

        $this->output
            ->expects($this->exactly(7))
            ->method('write')
            ->withConsecutive(
                ['+------+---------------------------+', true],
                ['| RAN? | MIGRATION                 |', true],
                ['+------+---------------------------+', true],
                ['| Y    | 123_create_users_table    |', true],
                ['| N    | 456_create_profiles_table |', true],
                ['| N    | 789_create_roles_table    |', true],
                ['+------+---------------------------+', true]
            );

        $this->dispatchCli('quark migrate:status');
    }
}
