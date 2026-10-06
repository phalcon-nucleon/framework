<?php

declare(strict_types=1);

namespace Test\Database\Migrations\Storage;

use Neutrino\Constants\Services;
use Neutrino\Database\Migrations\Storage\Database\MigrationModel;
use Neutrino\Database\Migrations\Storage\DatabaseStorage;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\Database\Migrations\MigrationsTestCase;

final class DatabaseStorageTest extends MigrationsTestCase
{
    #[DataProvider('connections')]
    public function testStorage(string $database): void
    {
        $this->boot($database);
        $storage = $this->storage();

        $this->assertInstanceOf(DatabaseStorage::class, $storage);
        $this->assertFalse($storage->storageExist());
        $this->assertTrue($storage->createStorage());
        $this->assertTrue($storage->storageExist());
        $this->assertSame([], $storage->getRan());
        $this->assertSame([], $storage->getLast());
        $this->assertSame(0, $storage->getLastBatchNumber());
        $this->assertSame(1, $storage->getNextBatchNumber());

        $storage->log('3_c', 1);
        $storage->log('1_a', 1);
        $storage->log('2_b', 2);
        $storage->log('4_d', 2);

        $this->assertSame(['1_a', '3_c', '2_b', '4_d'], $storage->getRan());
        $this->assertSame(2, $storage->getLastBatchNumber());
        $this->assertSame(3, $storage->getNextBatchNumber());
        $this->assertSame([['migration' => '4_d', 'batch' => 2], ['migration' => '2_b', 'batch' => 2]], $storage->getLast());
        $this->assertSame([['migration' => '4_d', 'batch' => 2], ['migration' => '2_b', 'batch' => 2], ['migration' => '3_c', 'batch' => 1]], $storage->getMigrations(3));

        $storage->delete('4_d');
        $storage->delete('unknown');

        $this->assertSame(['1_a', '3_c', '2_b'], $storage->getRan());
        $this->assertSame([['migration' => '2_b', 'batch' => 2]], $storage->getLast());
    }

    public function testConnectionOfTheTable(): void
    {
        $this->boot('sqlite', ['migrations' => ['connection' => 'reports']]);
        $storage = $this->storage();

        $storage->createStorage();
        $storage->log('1_a', 1);

        $this->assertSame(['migrations'], $this->tables(Services::DB . '.reports'));
        $this->assertSame([], $this->tables());
        $this->assertSame(['1_a'], $storage->getRan());
        $this->assertSame('migrations', (new MigrationModel())->getSource());
    }
}
