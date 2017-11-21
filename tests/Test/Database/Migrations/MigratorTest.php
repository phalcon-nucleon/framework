<?php

namespace Test\Database\Migrations;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Prefix\TimestampPrefix;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Neutrino\Support\Reflacker;
use Test\TestCase\TestCase;

class MigratorTest extends TestCase
{
    public function testNotes()
    {
        $migrator = new Migrator($this->createMock(StorageInterface::class), new TimestampPrefix());

        Reflacker::invoke($migrator, 'note', "my first message");
        Reflacker::invoke($migrator, 'note', "my second message");

        $this->assertEquals([
            "my first message",
            "my second message"
        ], $migrator->getNotes());
    }

    public function testStorage()
    {
        $storage = $this->createMock(StorageInterface::class);

        $storage
            ->expects($this->once())
            ->method('storageExist')
            ->willReturn(true);

        $migrator = new Migrator($storage, new TimestampPrefix());

        $this->assertEquals($storage, $migrator->getStorage());
        $this->assertTrue($migrator->storageExist());
    }

    public function testPaths()
    {
        $migrator = new Migrator($this->createMock(StorageInterface::class), new TimestampPrefix());

        $migrator->path('path_1');
        $migrator->path('path_1');
        $migrator->path('path_2');

        $this->assertEquals(['path_1', 'path_2'], $migrator->paths());
    }

    public function testGetMigrationName()
    {
        $migrator = new Migrator($this->createMock(StorageInterface::class), new TimestampPrefix());

        $this->assertEquals('file_123', $migrator->getMigrationName(__DIR__ . '/file_123.php'));
    }

    public function testGetMigrationsFiles()
    {
        $migrator = new Migrator($this->createMock(StorageInterface::class), new TimestampPrefix());

        $files = $migrator->getMigrationFiles(__DIR__);

        $this->assertEquals([], $files);

        $files = $migrator->getMigrationFiles(__DIR__ . '/.data/migrations_dir_1');

        $this->assertEquals([
            '1511272551_CreateOne' => __DIR__ . '/.data/migrations_dir_1/1511272551_CreateOne.php',
            '1511272584_CreateTwo' => __DIR__ . '/.data/migrations_dir_1/1511272584_CreateTwo.php',
            '1511272593_UpdateOne' => __DIR__ . '/.data/migrations_dir_1/1511272593_UpdateOne.php',
        ], $files);

        $files = $migrator->getMigrationFiles([
            __DIR__ . '/.data/migrations_dir_1',
            __DIR__ . '/.data/migrations_dir_2'
        ]);

        $this->assertEquals([
            '1511272551_CreateOne' => __DIR__ . '/.data/migrations_dir_1/1511272551_CreateOne.php',
            '1511272584_CreateTwo' => __DIR__ . '/.data/migrations_dir_1/1511272584_CreateTwo.php',
            '1511272593_UpdateOne' => __DIR__ . '/.data/migrations_dir_1/1511272593_UpdateOne.php',
            '1511272605_CreateTree' => __DIR__ . '/.data/migrations_dir_2/1511272605_CreateTree.php',
            '1511272613_UpdateTwo' => __DIR__ . '/.data/migrations_dir_2/1511272613_UpdateTwo.php',
            '1511272620_UpdateTree' => __DIR__ . '/.data/migrations_dir_2/1511272620_UpdateTree.php',
        ], $files);
    }
}
