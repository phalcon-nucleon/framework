<?php

namespace Test\Database\Migrations;

use Neutrino\Database\Migrations\MigrationCreator;
use Neutrino\Database\Migrations\Prefix\DatePrefix;
use Neutrino\Database\Migrations\Prefix\PrefixInterface;
use Neutrino\Support\Reflacker;
use Neutrino\Support\Str;
use Test\TestCase\TestCase;

/**
 * Class MigrationCreatorTest
 *
 * @package Test\Database\Migrations
 */
class MigrationCreatorTest extends TestCase
{
    public function testGetClassName()
    {
        $migrationCreator = new MigrationCreator(new DatePrefix());

        $this->assertEquals('MyClassName', Reflacker::invoke($migrationCreator, 'getClassName', 'my_class_name'));
    }

    public function testGetPath()
    {
        $prefix = $this->createMock(PrefixInterface::class);

        $prefix
            ->expects($this->once())
            ->method('getPrefix')
            ->willReturn('prefix');

        $migrationCreator = new MigrationCreator($prefix);

        $this->assertEquals('path/prefix_name.php', Reflacker::invoke($migrationCreator, 'getPath', 'name', 'path'));
    }

    public function testStubsPath()
    {
        $migrationCreator = new MigrationCreator(new DatePrefix());

        $this->assertTrue(
            Str::endsWith(
                str_replace(DIRECTORY_SEPARATOR, '/', $migrationCreator->stubsPath()),
                'Neutrino/Database/Migrations/stubs'
            )
        );
    }

    public function dataPopulateStub()
    {
        return [
            ['class MyClassName table my_table', 'my_class_name', 'class {class} table {table}', 'my_table'],
            ['class MyClassName table {table}', 'my_class_name', 'class {class} table {table}', null],
        ];
    }

    /**
     * @dataProvider dataPopulateStub
     */
    public function testPopulateStub($expected, ...$arguments)
    {
        $migrationCreator = new MigrationCreator(new DatePrefix());

        $this->assertEquals($expected, Reflacker::invoke($migrationCreator, 'populateStub', ...$arguments));
    }

    public function dataGetStubContent()
    {
        return [
            'blank.stub'  => ['blank.stub', null, null],
            'update.stub' => ['update.stub', 'table', false],
            'create.stub' => ['create.stub', 'table', true],
        ];
    }

    /**
     * @dataProvider dataGetStubContent
     */
    public function testGetStubContent($expectedStub, $table, $create)
    {
        $dir = dirname((new \ReflectionClass(MigrationCreator::class))->getFileName());

        $migrationCreator = new MigrationCreator(new DatePrefix());

        $stub = Reflacker::invoke($migrationCreator, 'getStubContent', $table, $create);

        $this->assertStringEqualsFile($dir . '/stubs/' . $expectedStub, $stub);
    }

    public function testEnsureMigrationDoesntAlreadyExist()
    {
        $migrationCreator = new MigrationCreator(new DatePrefix());

        Reflacker::invoke($migrationCreator, 'ensureMigrationDoesntAlreadyExist', 'my_class_name', self::$tmpPath);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEnsureMigrationDoesntAlreadyExistFail()
    {
        $prefix = $this->createMock(PrefixInterface::class);

        $prefix
            ->expects($this->once())
            ->method('getPrefix')
            ->willReturn('prefix');

        $migrationCreator = new MigrationCreator($prefix);

        $migrationCreator->create('my_class_name', self::$tmpPath);

        Reflacker::invoke($migrationCreator, 'ensureMigrationDoesntAlreadyExist', 'my_class_name', self::$tmpPath);
    }

    public function dataCreate()
    {
        return [
            'blank.stub'  => [null, null],
            'update.stub' => ['table', false],
            'create.stub' => ['create.stub', 'table', true],
        ];
    }

    /**
     * @dataProvider dataCreate
     */
    public function testCreate($table, $create)
    {
        $prefix = $this->createMock(PrefixInterface::class);

        $prefix
            ->expects($this->once())
            ->method('getPrefix')
            ->willReturn('prefix');

        $migrationCreator = new MigrationCreator($prefix);

        $migrationCreator->create('my_class_name', self::$tmpPath . '', $table, $create);

        $stub = Reflacker::invoke($migrationCreator, 'getStubContent', $table, $create);
        $stub = Reflacker::invoke($migrationCreator, 'populateStub', 'my_class_name', $stub, $table);

        $this->assertFileExists(self::$tmpPath . '/prefix_my_class_name.php');
        $this->assertEquals(
            $stub,
            file_get_contents(self::$tmpPath . '/prefix_my_class_name.php')
        );
    }

    public function setUp()
    {
        parent::setUp();

        @mkdir(self::$tmpPath);
    }

    public function tearDown()
    {
        parent::tearDown();

        foreach (glob(self::$tmpPath . '/*') as $item) {
            @unlink($item);
        }

        @rmdir(self::$tmpPath);
    }

    protected static $tmpPath = __DIR__ . '/tmp';
}
