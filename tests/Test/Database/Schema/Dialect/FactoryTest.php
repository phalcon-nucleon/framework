<?php

namespace Test\Database\Schema\Dialect;

use Neutrino\Database\Schema\Dialect as Schema;
use Neutrino\Database\Schema\DialectInterface as SchemaInterface;
use Phalcon\Db\Dialect;
use Phalcon\Db\DialectInterface;
use Test\TestCase\TestCase;

class FactoryTest extends TestCase
{
    public function dataFactory()
    {
        return [
            'SchemaInterface' => [SchemaInterface::class, SchemaInterface::class],
            'Dialect\Mysql' => [Schema\Mysql::class, Dialect\Mysql::class],
            'Dialect\Postgresql' => [Schema\Postgresql::class, Dialect\Postgresql::class],
            'Dialect\Sqlite' => [Schema\Sqlite::class, Dialect\Sqlite::class],
            'Dialect\Wrapper' => [Schema\Wrapper::class, DialectInterface::class],
        ];
    }

    /**
     * @dataProvider dataFactory
     *
     * @param $expectedClass
     * @param $givenClass
     */
    public function testFactory($expectedClass, $givenClass)
    {
        $this->assertInstanceOf($expectedClass, Schema\Factory::create($this->createMock($givenClass)));
    }
}
