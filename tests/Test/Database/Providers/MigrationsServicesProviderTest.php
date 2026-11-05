<?php

declare(strict_types=1);

namespace Test\Database\Providers;

use Neutrino\Database\Cli\Tasks\MakerTask;
use Neutrino\Database\Cli\Tasks\MigrateTask;
use Neutrino\Database\Migrations\MigrationCreator;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Prefix\DatePrefix;
use Neutrino\Database\Migrations\Prefix\PrefixInterface;
use Neutrino\Database\Migrations\Prefix\TimestampPrefix;
use Neutrino\Database\Migrations\Storage\DatabaseStorage;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Neutrino\Database\Providers\MigrationsServicesProvider;
use RuntimeException;
use Test\Database\Migrations\MigrationsTestCase;

final class MigrationsServicesProviderTest extends MigrationsTestCase
{
    public function testServices(): void
    {
        $di = $this->boot();

        $this->assertInstanceOf(TimestampPrefix::class, $di->getShared(PrefixInterface::class));
        $this->assertInstanceOf(DatabaseStorage::class, $di->getShared(StorageInterface::class));
        $this->assertInstanceOf(Migrator::class, $di->getShared(Migrator::class));
        $this->assertSame($di->getShared(Migrator::class), $di->getShared(Migrator::class));
        $this->assertSame($di->getShared(StorageInterface::class), $this->migrator()->getStorage());
        $this->assertInstanceOf(MigrationCreator::class, $di->getShared(MigrationCreator::class));
    }

    public function testConfiguredClasses(): void
    {
        $di = $this->boot('sqlite', ['migrations' => ['prefix' => DatePrefix::class]]);

        $this->assertInstanceOf(DatePrefix::class, $di->getShared(PrefixInterface::class));
    }

    public function testRemovedFileStorage(): void
    {
        $di = $this->boot('sqlite', ['migrations' => ['storage' => 'Neutrino\Database\Migrations\Storage\FileStorage']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The migrations FileStorage was removed in Nucleon 2.0');

        $di->getShared(StorageInterface::class);
    }

    public function testInvalidClass(): void
    {
        $di = $this->boot('sqlite', ['migrations' => ['prefix' => \stdClass::class]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('migrations.prefix: stdClass is not a class implementing ' . PrefixInterface::class . '.');

        $di->getShared(PrefixInterface::class);
    }

    public function testTasks(): void
    {
        $tasks = MigrationsServicesProvider::tasks();

        $this->assertSame(MigrateTask::class, $tasks['migrate']);
        $this->assertSame(MakerTask::class, $tasks['make:migration {name}']);
        $this->assertCount(8, $tasks);
    }
}
