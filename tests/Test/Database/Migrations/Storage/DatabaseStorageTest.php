<?php

namespace Test\Database\Migrations\Storage;

use Neutrino\Constants\Services;
use Neutrino\Database\Migrations\Storage\Database\MigrationModel;
use Neutrino\Database\Migrations\Storage\Database\MigrationRepository;
use Neutrino\Database\Migrations\Storage\DatabaseStorage;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapterMysql;
use Phalcon\Db\Column;
use Phalcon\Db\Dialect\Mysql as DbDialectMysql;
use Phalcon\Mvc\Model\Resultset\Simple;
use Test\TestCase\TestCase;

/**
 * Class DatabaseStorageTest
 *
 * @package Test\Database\Migrations\Storage
 */
class DatabaseStorageTest extends TestCase
{
    private function mockDb($class = null, $description = null, $withDialect = true)
    {
        if (is_null($class)) {
            $class = DbAdapterMysql::class;
        }
        if (is_null($description)) {
            $description = [
                'host'     => 'localhost',
                'username' => 'utest',
                'password' => 'pwd',
                'dbname'   => 'test',
                'charset'  => 'utf8',
            ];
        }

        if ($this->getDI()->has(Services::DB)) {
            return $this->getDI()->get(Services::DB);
        }

        $db = $this->mockService(Services::DB, $class, true);

        $db->expects($this->any())
            ->method("getDescriptor")
            ->willReturn($description);

        if ($withDialect) {
            $dialect = $this->createMock(DbDialectMysql::class);

            $db->expects($this->any())
                ->method("getDialect")
                ->willReturn($dialect);
        }

        return $db;
    }

    private function mockResultset($data)
    {
        $results = $this->createMock(\Phalcon\Db\Result\Pdo::class);

        $results->expects($this->any())
            ->method('numRows')
            ->willReturn(count($data));

        $results->expects($this->any())
            ->method('fetchAll')
            ->willReturn($data);

        return new Simple(['migration' => 'migration', 'batch' => 'batch'], MigrationModel::class, $results);
    }

    public function testGetRan()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $mock
            ->expects($this->once())
            ->method('find')
            ->with([], [
                'batch'     => 'ASC',
                'migration' => 'ASC'
            ])
            ->willReturn(
                $this->mockResultset([['migration' => 'test', 'batch' => 1]])
            );

        $databaseStorage = new DatabaseStorage();

        $data = $databaseStorage->getRan();

        $this->assertEquals(['test'], $data);
    }

    public function testGetMigration()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $mock
            ->expects($this->once())
            ->method('find')
            ->with(
                [
                    'batch' => [
                        'operator' => '>=',
                        'value'    => 1
                    ]
                ],
                [
                    'batch'     => 'DESC',
                    'migration' => 'DESC'
                ],
                2
            )
            ->willReturn(
                $this->mockResultset([
                    ['migration' => 'test', 'batch' => 1],
                    ['migration' => 'test', 'batch' => 2]
                ])
            );

        $databaseStorage = new DatabaseStorage();

        $data = $databaseStorage->getMigrations(2);

        $this->assertEquals([
            ['migration' => 'test', 'batch' => 1],
            ['migration' => 'test', 'batch' => 2]
        ], $data);
    }

    public function dataStorageExist()
    {
        return [
            [true, true],
            [false, false]
        ];
    }

    /**
     * @dataProvider dataStorageExist
     */
    public function testStorageExist($expected, $exist)
    {
        $db = $this->mockDb();

        $db->expects($this->once())
            ->method('tableExists')
            ->with('migrations')
            ->willReturn($exist);

        $databaseStorage = new DatabaseStorage();

        $this->assertEquals($expected, $databaseStorage->storageExist());
    }

    public function testCreateStorage()
    {
        $db = $this->mockDb();

        $db->expects($this->once())
            ->method("createTable")
            ->with('migrations', null, [
                'columns' => [
                    new Column('id', [
                        'autoIncrement' => true,
                        'type'          => Column::TYPE_INTEGER,
                        'unsigned'      => true,
                        'notNull'       => true,
                        'primary'       => true
                    ]),
                    new Column('migration', [
                        'type'    => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size'    => 256
                    ]),
                    new Column('batch', [
                        'type'     => Column::TYPE_INTEGER,
                        'unsigned' => true,
                        'notNull'  => true,
                    ]),
                ]
            ]);

        $databaseStorage = new DatabaseStorage();

        $databaseStorage->createStorage();
    }

    public function testGetLastBatchNumber()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $mock
            ->expects($this->once())
            ->method('maximum')
            ->with('batch')
            ->willReturn(1);

        $databaseStorage = new DatabaseStorage();

        $this->assertEquals(1, $databaseStorage->getLastBatchNumber());
    }

    public function testGetNextBatchNumber()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $mock
            ->expects($this->once())
            ->method('maximum')
            ->with('batch')
            ->willReturn(1);

        $databaseStorage = new DatabaseStorage();

        $this->assertEquals(2, $databaseStorage->getNextBatchNumber());
    }

    public function testGetLastNoData()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $mock
            ->expects($this->once())
            ->method('first')
            ->with(['batch' => 0], ['migration' => 'DESC'])
            ->willReturn(
                false
            );

        $databaseStorage = new DatabaseStorage();

        $this->assertEquals([], $databaseStorage->getLast());
    }

    public function testGetLast()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $mock
            ->expects($this->once())
            ->method('first')
            ->with(['batch' => 0], ['migration' => 'DESC'])
            ->willReturn(
                new MigrationModel([
                    'id'        => 1,
                    'migration' => 'test',
                    'batch'     => 1
                ])
            );

        $databaseStorage = new DatabaseStorage();

        $this->assertEquals([[
            'id'        => 1,
            'migration' => 'test',
            'batch'     => 1,
        ]], $databaseStorage->getLast());
    }

    public function testLogSuccess()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $mock
            ->expects($this->once())
            ->method('create')
            ->with(new MigrationModel([
                'migration' => 'test',
                'batch'     => 1
            ]))
            ->willReturn(true);


        $databaseStorage = new DatabaseStorage();

        $databaseStorage->log('test', 1);
    }

    /**
     * @expectedException \Exception
     */
    public function testLogFail()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $mock
            ->expects($this->once())
            ->method('create')
            ->with(new MigrationModel([
                'migration' => 'test',
                'batch'     => 1
            ]))
            ->willReturn(false);

        $mock
            ->expects($this->once())
            ->method('getMessages')
            ->willReturn(['Exception message']);

        $databaseStorage = new DatabaseStorage();

        $databaseStorage->log('test', 1);
    }

    public function testDeleteSuccess()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $migration = new MigrationModel([
            'id'        => 1,
            'migration' => 'test',
            'batch'     => 1
        ]);

        $mock
            ->expects($this->once())
            ->method('first')
            ->with(['migration' => 'test'])
            ->willReturn(
                $migration
            );

        $mock
            ->expects($this->once())
            ->method('delete')
            ->with($migration)
            ->willReturn(true);

        $databaseStorage = new DatabaseStorage();

        $databaseStorage->delete('test');
    }

    /**
     * @expectedException \Exception
     */
    public function testDeleteFail()
    {
        $mock = $this->mockService(MigrationRepository::class, MigrationRepository::class, true);

        $migration = new MigrationModel([
            'id'        => 1,
            'migration' => 'test',
            'batch'     => 1
        ]);

        $mock
            ->expects($this->once())
            ->method('first')
            ->with(['migration' => 'test'])
            ->willReturn(
                $migration
            );

        $mock
            ->expects($this->once())
            ->method('delete')
            ->with($migration)
            ->willReturn(false);

        $mock
            ->expects($this->once())
            ->method('getMessages')
            ->willReturn(['Exception message']);

        $databaseStorage = new DatabaseStorage();

        $databaseStorage->delete('test');
    }
}
