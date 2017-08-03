<?php

namespace Test\Database\Schema\Dialect;

use Neutrino\Database\Schema\Dialect\Postgresql;
use Neutrino\Support\Fluent;
use Phalcon\Db\Column;

class PostgresqlTest extends \PHPUnit_Framework_TestCase
{
    public function dataType()
    {
        return [
            ['boolean', new Fluent(['name' => 'col', 'type' => 'boolean']), ['type' => Column::TYPE_BOOLEAN]],
            ['tinyInteger', new Fluent(['name' => 'col', 'type' => 'tinyInteger']), ['type' => 'TINYINT', 'typeReference' => Column::TYPE_INTEGER]],
            ['smallInteger', new Fluent(['name' => 'col', 'type' => 'smallInteger']), ['type' => 'SMALLINT', 'typeReference' => Column::TYPE_INTEGER]],
            ['mediumInteger', new Fluent(['name' => 'col', 'type' => 'mediumInteger']), ['type' => 'MEDIUMINT', 'typeReference' => Column::TYPE_INTEGER]],
            ['integer', new Fluent(['name' => 'col', 'type' => 'integer']), ['type' => Column::TYPE_INTEGER]],
            ['bigInteger', new Fluent(['name' => 'col', 'type' => 'bigInteger']), ['type' => Column::TYPE_BIGINTEGER]],
            ['decimal', new Fluent(['name' => 'col', 'type' => 'decimal']), ['type' => Column::TYPE_DECIMAL]],
            ['double', new Fluent(['name' => 'col', 'type' => 'double']), ['type' => Column::TYPE_DOUBLE]],
            ['float', new Fluent(['name' => 'col', 'type' => 'float']), ['type' => Column::TYPE_FLOAT]],
            ['json', new Fluent(['name' => 'col', 'type' => 'json']), ['type' => Column::TYPE_JSON]],
            ['jsonb', new Fluent(['name' => 'col', 'type' => 'jsonb']), ['type' => Column::TYPE_JSONB]],
            ['char', new Fluent(['name' => 'col', 'type' => 'char']), ['type' => Column::TYPE_CHAR]],
            ['string', new Fluent(['name' => 'col', 'type' => 'string']), ['type' => Column::TYPE_VARCHAR]],
            ['mediumText', new Fluent(['name' => 'col', 'type' => 'mediumText']), ['type' => Column::TYPE_TEXT]],
            ['text', new Fluent(['name' => 'col', 'type' => 'text']), ['type' => Column::TYPE_TEXT]],
            ['longText', new Fluent(['name' => 'col', 'type' => 'longText']), ['type' => Column::TYPE_TEXT]],
            ['enum', new Fluent(['name' => 'col', 'type' => 'enum', 'values' => ['a', 'b']]), ['type' => 'varchar(3) check ("col" in (\'a\', \'b\'))', 'typeReference' => -1]],
            ['blob', new Fluent(['name' => 'col', 'type' => 'blob']), ['type' => Column::TYPE_BLOB]],
            ['tinyBlob', new Fluent(['name' => 'col', 'type' => 'tinyBlob']), ['type' => Column::TYPE_TINYBLOB]],
            ['mediumBlob', new Fluent(['name' => 'col', 'type' => 'mediumBlob']), ['type' => Column::TYPE_MEDIUMBLOB]],
            ['longBlob', new Fluent(['name' => 'col', 'type' => 'longBlob']), ['type' => Column::TYPE_LONGBLOB]],
            ['date', new Fluent(['name' => 'col', 'type' => 'date']), ['type' => Column::TYPE_DATE]],
            ['dateTime', new Fluent(['name' => 'col', 'type' => 'dateTime']), ['type' => Column::TYPE_DATETIME]],
            ['dateTimeTz', new Fluent(['name' => 'col', 'type' => 'dateTimeTz']), ['type' => 'DATETIME WITH TIME ZONE', 'typeReference' => Column::TYPE_DATETIME]],
            ['time', new Fluent(['name' => 'col', 'type' => 'time']), ['type' => 'TIME', 'typeReference' => Column::TYPE_DATETIME]],
            ['timeTz', new Fluent(['name' => 'col', 'type' => 'timeTz']), ['type' => 'TIME WITH TIME ZONE', 'typeReference' => Column::TYPE_DATETIME]],
            ['timestamp', new Fluent(['name' => 'col', 'type' => 'timestamp']), ['type' => Column::TYPE_TIMESTAMP]],
            ['timestampTz', new Fluent(['name' => 'col', 'type' => 'timestampTz']), ['type' => 'TIMESTAMP WITH TIME ZONE', 'typeReference' => Column::TYPE_TIMESTAMP]],
            ['uuid', new Fluent(['name' => 'col', 'type' => 'uuid']), ['type' => 'uuid', 'typeReference' => Column::TYPE_CHAR]],
            ['ipAddress', new Fluent(['name' => 'col', 'type' => 'ipAddress']), ['type' => 'inet', 'typeReference' => Column::TYPE_VARCHAR]],
            ['macAddress', new Fluent(['name' => 'col', 'type' => 'macAddress']), ['type' => 'macaddr', 'typeReference' => Column::TYPE_VARCHAR]],
        ];
    }

    /**
     * @dataProvider dataType
     */
    public function testType($type, $column, $expected)
    {
        $dialect = new Postgresql();

        $this->assertEquals($expected, $dialect->{'type' . ucfirst($type)}($column));
    }

    /**
     * @dataProvider dataType
     */
    public function testGetType($type, $column, $expected)
    {
        $dialect = new Postgresql();

        $this->assertEquals($expected, $dialect->getType($column));
    }

    public function testEnableForeignKeyConstraints()
    {
        $dialect = new Postgresql();

        $this->assertEquals('SET CONSTRAINTS ALL IMMEDIATE;', $dialect->enableForeignKeyConstraints());
    }

    public function testDisableForeignKeyConstraints()
    {
        $dialect = new Postgresql();

        $this->assertEquals('SET CONSTRAINTS ALL DEFERRED;', $dialect->disableForeignKeyConstraints());
    }
}
