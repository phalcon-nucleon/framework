<?php

namespace Test\Database\Migrations\Storage;

use Neutrino\Database\Migrations\Storage\FileStorage;
use Neutrino\Debug\Reflexion;
use Test\TestCase\TestCase;

/**
 * Class FileStorageTest
 *
 * @package Test\Database\Migrations\Storage
 */
class FileStorageTest extends TestCase
{
    private static $storageFilePath = BASE_PATH . '/migrations/.migrations.dat';

    public function tearDown()
    {
        parent::tearDown();

        if (file_exists(self::$storageFilePath)) {
            @unlink(self::$storageFilePath);
        }
    }

    public function testCreateStorage()
    {
        $fileStorage = new FileStorage();

        $this->assertTrue($fileStorage->createStorage());
        $this->assertFileExists(self::$storageFilePath);
        $this->assertEquals('[]', file_get_contents(self::$storageFilePath));

        $this->assertTrue($fileStorage->createStorage());
    }

    public function testStorageExist()
    {
        $fileStorage = new FileStorage();

        $this->assertFalse($fileStorage->storageExist());

        $fileStorage->createStorage();

        $this->assertTrue($fileStorage->storageExist());
    }

    public function testGetDataNoStorage()
    {
        $fileStorage = new FileStorage();

        $expected = [];

        $data = Reflexion::invoke($fileStorage, 'getData');

        $this->assertEquals($expected, $data);
    }

    public function testGetDataEmptyStorage()
    {
        $fileStorage = new FileStorage();

        $expected = [];

        file_put_contents(self::$storageFilePath, '');

        $data = Reflexion::invoke($fileStorage, 'getData');

        $this->assertEquals($expected, $data);
    }

    public function testGetData()
    {
        $fileStorage = new FileStorage();

        $expected = [['migration' => __FILE__, 'batch' => 1]];

        file_put_contents(self::$storageFilePath, json_encode([['migration' => __FILE__, 'batch' => 1]]));

        $data = Reflexion::invoke($fileStorage, 'getData');

        $this->assertEquals($expected, $data);
    }

    public function testSetData()
    {
        $fileStorage = new FileStorage();

        $expected = [['migration' => __FILE__, 'batch' => 1]];

        Reflexion::invoke($fileStorage, 'setData', $expected);

        $data = Reflexion::get($fileStorage, 'data');

        $this->assertEquals($expected, $data);
        $this->assertFileExists(self::$storageFilePath);
        $this->assertEquals($expected, json_decode(file_get_contents(self::$storageFilePath), true));
    }

    public function testGetLastBatchNumber()
    {
        $fileStorage = new FileStorage();

        file_put_contents(self::$storageFilePath, json_encode([
            ['migration' => __FILE__, 'batch' => 1],
            ['migration' => __FILE__, 'batch' => 2],
            ['migration' => __FILE__, 'batch' => 3],
        ]));

        $this->assertEquals(3, $fileStorage->getLastBatchNumber());
    }

    public function testGetNextBatchNumber()
    {
        $fileStorage = new FileStorage();

        file_put_contents(self::$storageFilePath, json_encode([
            ['migration' => __FILE__, 'batch' => 1],
            ['migration' => __FILE__, 'batch' => 2],
            ['migration' => __FILE__, 'batch' => 3],
        ]));

        $this->assertEquals(4, $fileStorage->getNextBatchNumber());
    }

    public function testGetLastNoData()
    {
        $fileStorage = new FileStorage();

        file_put_contents(self::$storageFilePath, json_encode([]));

        $this->assertEquals([], $fileStorage->getLast());
    }

    public function testGetLast()
    {
        $fileStorage = new FileStorage();

        file_put_contents(self::$storageFilePath, json_encode([
            ['migration' => '123_' . __FILE__, 'batch' => 1],
            ['migration' => '456_' . __FILE__, 'batch' => 2],
            ['migration' => '789_' . __FILE__, 'batch' => 3],
        ]));

        $this->assertEquals([['migration' => '789_' . __FILE__, 'batch' => 3]], $fileStorage->getLast());
    }

    public function testLog()
    {
        $fileStorage = new FileStorage();

        file_put_contents(self::$storageFilePath, json_encode([
            ['migration' => '123_' . __FILE__, 'batch' => 1],
            ['migration' => '456_' . __FILE__, 'batch' => 2],
        ]));

        $fileStorage->log('789_' . __FILE__, 3);

        $data = Reflexion::get($fileStorage, 'data');

        $this->assertEquals([
            ['migration' => '123_' . __FILE__, 'batch' => 1],
            ['migration' => '456_' . __FILE__, 'batch' => 2],
            ['migration' => '789_' . __FILE__, 'batch' => 3],
        ], $data);
        $this->assertEquals($data, json_decode(file_get_contents(self::$storageFilePath), true));
    }

    public function testDelete()
    {
        $fileStorage = new FileStorage();

        file_put_contents(self::$storageFilePath, json_encode([
            ['migration' => '123_' . __FILE__, 'batch' => 1],
            ['migration' => '456_' . __FILE__, 'batch' => 2],
            ['migration' => '789_' . __FILE__, 'batch' => 3],
        ]));

        $fileStorage->delete('789_' . __FILE__);

        $data = Reflexion::get($fileStorage, 'data');

        $this->assertEquals([
            ['migration' => '123_' . __FILE__, 'batch' => 1],
            ['migration' => '456_' . __FILE__, 'batch' => 2],
        ], $data);
        $this->assertEquals($data, json_decode(file_get_contents(self::$storageFilePath), true));
    }

    public function testGetMigration()
    {
        $fileStorage = new FileStorage();

        file_put_contents(self::$storageFilePath, json_encode([
            ['migration' => '789_' . __FILE__, 'batch' => 5],
            ['migration' => '456_' . __FILE__, 'batch' => 2],
            ['migration' => '123_' . __FILE__, 'batch' => 6],
            ['migration' => '789_' . __FILE__, 'batch' => 3],
            ['migration' => '123_' . __FILE__, 'batch' => 1],
            ['migration' => '456_' . __FILE__, 'batch' => 4],
        ]));

        $data = $fileStorage->getMigrations(2);

        $this->assertEquals([
            ['migration' => '123_' . __FILE__, 'batch' => 6],
            ['migration' => '789_' . __FILE__, 'batch' => 5],
        ], $data);

        $data = $fileStorage->getMigrations(3);

        $this->assertEquals([
            ['migration' => '123_' . __FILE__, 'batch' => 6],
            ['migration' => '789_' . __FILE__, 'batch' => 5],
            ['migration' => '456_' . __FILE__, 'batch' => 4],
        ], $data);
    }

    public function testGetRan()
    {
        $fileStorage = new FileStorage();

        file_put_contents(self::$storageFilePath, json_encode([
            ['migration' => '789_' . __FILE__, 'batch' => 3],
            ['migration' => '123_' . __FILE__, 'batch' => 1],
            ['migration' => '789_' . __FILE__, 'batch' => 5],
            ['migration' => '456_' . __FILE__, 'batch' => 2],
            ['migration' => '123_' . __FILE__, 'batch' => 6],
            ['migration' => '456_' . __FILE__, 'batch' => 4],
        ]));

        $data = $fileStorage->getRan();

        $this->assertEquals([
            '123_' . __FILE__,
            '456_' . __FILE__,
            '789_' . __FILE__,
            '456_' . __FILE__,
            '789_' . __FILE__,
            '123_' . __FILE__,
        ], $data);
    }
}
