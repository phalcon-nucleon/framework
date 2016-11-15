<?php

namespace Test\Models;

use Luxury\Model;
use Phalcon\Db\Column;
use Phalcon\Mvc\Model\MetaData;
use Test\TestCase\TestCase;

class ModelTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setStaticValueProperty(Model::class, 'columnsMapClass', []);
        $this->setStaticValueProperty(Model::class, 'metaDatasClass', []);
    }

    public function assertColumnBind($name, $expectedBind, $numeric)
    {
        $meta = $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class];

        $bind = $meta[MetaData::MODELS_DATA_TYPES_BIND][$name];

        $this->assertEquals($expectedBind, $bind);

        if ($numeric) {
            $this->assertArrayHasKey(MetaData::MODELS_DATA_TYPES_NUMERIC, $meta);
            $this->assertArrayHasKey($name, $meta[MetaData::MODELS_DATA_TYPES_NUMERIC]);
        } elseif(isset($meta[MetaData::MODELS_DATA_TYPES_NUMERIC])) {
            $this->assertArrayNotHasKey($name, $meta[MetaData::MODELS_DATA_TYPES_NUMERIC]);
        }
    }

    public function assertColumnAdded($name, $type, $expectedBind, $numeric)
    {

        $meta = $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class];

        $this->assertColumnBind($name, $expectedBind, $numeric);

        $this->assertArrayHasKey(MetaData::MODELS_ATTRIBUTES, $meta);
        $this->assertTrue(in_array($name, $meta[MetaData::MODELS_ATTRIBUTES]));

        $this->assertArrayHasKey(MetaData::MODELS_DATA_TYPES, $meta);
        $this->assertArrayHasKey($name, $meta[MetaData::MODELS_DATA_TYPES]);
        $this->assertEquals($type, $meta[MetaData::MODELS_DATA_TYPES][$name]);

        $columns = $this->getStaticValueProperty(Model::class, 'columnsMapClass')[Model::class];

        $this->assertArrayHasKey($name, $columns);
        $this->assertEquals($name, $columns[$name]);
    }

    public function dataDescribeColumnType()
    {
        return [
            [Column::TYPE_BIGINTEGER, Column::BIND_PARAM_INT, true],
            [Column::TYPE_INTEGER, Column::BIND_PARAM_INT, true],
            [Column::TYPE_TIMESTAMP, Column::BIND_PARAM_INT, true],
            [Column::TYPE_DECIMAL, Column::BIND_PARAM_DECIMAL, true],
            [Column::TYPE_FLOAT, Column::BIND_PARAM_DECIMAL, true],
            [Column::TYPE_DOUBLE, Column::BIND_PARAM_DECIMAL, true],
            [Column::TYPE_JSON, Column::BIND_PARAM_STR],
            [Column::TYPE_TEXT, Column::BIND_PARAM_STR],
            [Column::TYPE_CHAR, Column::BIND_PARAM_STR],
            [Column::TYPE_VARCHAR, Column::BIND_PARAM_STR],
            [Column::TYPE_DATE, Column::BIND_PARAM_STR],
            [Column::TYPE_DATETIME, Column::BIND_PARAM_STR],
            [Column::TYPE_BLOB, Column::BIND_PARAM_BLOB],
            [Column::TYPE_JSONB, Column::BIND_PARAM_BLOB],
            [Column::TYPE_MEDIUMBLOB, Column::BIND_PARAM_BLOB],
            [Column::TYPE_TINYBLOB, Column::BIND_PARAM_BLOB],
            [Column::TYPE_LONGBLOB, Column::BIND_PARAM_BLOB],
            [Column::TYPE_BOOLEAN, Column::BIND_PARAM_BOOL],
            [null, Column::BIND_PARAM_NULL],
            ['abc', Column::BIND_SKIP],
        ];
    }

    /**
     * @dataProvider dataDescribeColumnType
     */
    public function testDescribeColumnType($type, $expectedBind, $numeric = false)
    {
        $this->invokeStaticMethod(Model::class, 'describeColumnType', ['test', $type]);

        $this->assertColumnBind('test', $expectedBind, $numeric);
    }

    public function dataAddColumn()
    {
        $dataDescribes = $this->dataDescribeColumnType();

        foreach ($dataDescribes as $key => &$dataDescribe) {
            array_unshift($dataDescribe, 'test_' . $key);
        }

        return $dataDescribes;
    }

    /**
     * @dataProvider dataAddColumn
     */
    public function testAddColumn($name, $type, $expectedBind, $numeric = false)
    {
        $this->invokeStaticMethod(Model::class, 'addColumn', [$name, $type]);

        $this->assertColumnAdded($name, $type, $expectedBind, $numeric);
    }

    public function dataColumn()
    {
        return [
            ['test', Column::TYPE_BIGINTEGER, false, null, false, Column::BIND_PARAM_INT, true],
            ['test', Column::TYPE_INTEGER, true, null, false, Column::BIND_PARAM_INT, true],
            ['test', Column::TYPE_TIMESTAMP, false, 1, false, Column::BIND_PARAM_INT, true],
            ['test', Column::TYPE_DECIMAL, true, 1, false, Column::BIND_PARAM_DECIMAL, true],
            ['test', Column::TYPE_FLOAT, false, null, true, Column::BIND_PARAM_DECIMAL, true],
            ['test', Column::TYPE_DOUBLE, true, null, true, Column::BIND_PARAM_DECIMAL, true],
            ['test', Column::TYPE_JSON, false, 1, true, Column::BIND_PARAM_STR],
            ['test', Column::TYPE_TEXT, true, 1, true, Column::BIND_PARAM_STR],
            ['test', Column::TYPE_CHAR, false, null, false, Column::BIND_PARAM_STR],
            ['test', Column::TYPE_VARCHAR, false, null, false, Column::BIND_PARAM_STR],
            ['test', Column::TYPE_DATE, false, null, false, Column::BIND_PARAM_STR],
            ['test', Column::TYPE_DATETIME, false, null, false, Column::BIND_PARAM_STR],
            ['test', Column::TYPE_BLOB, false, null, false, Column::BIND_PARAM_BLOB],
            ['test', Column::TYPE_JSONB, false, null, false, Column::BIND_PARAM_BLOB],
            ['test', Column::TYPE_MEDIUMBLOB, false, null, false, Column::BIND_PARAM_BLOB],
            ['test', Column::TYPE_TINYBLOB, false, null, false, Column::BIND_PARAM_BLOB],
            ['test', Column::TYPE_LONGBLOB, false, null, false, Column::BIND_PARAM_BLOB],
            ['test', Column::TYPE_BOOLEAN, false, null, false, Column::BIND_PARAM_BOOL],
            ['test', null, false, null, false, Column::BIND_PARAM_NULL],
            ['test', 'abc', false, null, false, Column::BIND_SKIP],
        ];
    }

    /**
     * @dataProvider dataColumn
     */
    public function testColumn($name, $type, $nullable, $default, $autoUpdate, $expectedBind, $numeric = false)
    {
        $this->invokeStaticMethod(Model::class, 'column', [$name, $type, $nullable, $default, $autoUpdate]);

        $meta = $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class];

        $this->assertColumnAdded($name, $type, $expectedBind, $numeric);

        if ($nullable) {
            $this->assertArrayHasKey(MetaData::MODELS_EMPTY_STRING_VALUES, $meta);
            $this->assertEquals([$name => true], $meta[MetaData::MODELS_EMPTY_STRING_VALUES]);
        } else {
            $this->assertArrayHasKey(MetaData::MODELS_NOT_NULL, $meta);
            $this->assertEquals([$name], $meta[MetaData::MODELS_NOT_NULL]);
        }

        if (!is_null($default)) {
            $this->assertArrayHasKey(MetaData::MODELS_DEFAULT_VALUES, $meta);
            $this->assertEquals([$name => $default], $meta[MetaData::MODELS_DEFAULT_VALUES]);
        } else {
            $this->assertArrayNotHasKey(MetaData::MODELS_DEFAULT_VALUES, $meta);
        }

        if ($autoUpdate) {
            $this->assertArrayHasKey(MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE, $meta);
            $this->assertEquals([$name => true], $meta[MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE]);
        }

        $columns = $this->getStaticValueProperty(Model::class, 'columnsMapClass')[Model::class];

        $this->assertEquals([$name => $name], $columns);
    }

    public function testFullColumns()
    {
        $columns = $this->dataColumn();

        $names = [];
        $attrs = [];
        $types = [];
        $nulls = [];
        $defaults = [];
        $autos = [];
        $binds = [];
        foreach ($columns as $column) {
            $this->invokeStaticMethod(Model::class, 'column', $column);

            $name = array_shift($column);
            $attrs[] = $name;
            $names[$name] = $name;
            $types[$name] = array_shift($column);
            $nulls[$name] = array_shift($column);
            $defaults[$name] = array_shift($column);
            $autos[$name] = array_shift($column);
            $binds[$name] = array_shift($column);
        }

        $columns = $this->getStaticValueProperty(Model::class, 'columnsMapClass')[Model::class];
        $this->assertEquals($names, $columns);

        $meta = $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class];
        $this->assertEquals($attrs, $meta[MetaData::MODELS_ATTRIBUTES]);
        $this->assertEquals($binds, $meta[MetaData::MODELS_DATA_TYPES_BIND]);
    }

    public function dataPrimary()
    {
        return [
            ['primary', Column::TYPE_BIGINTEGER, true, true],
            ['primary', Column::TYPE_BIGINTEGER, false, true],
            ['primary', Column::TYPE_BIGINTEGER, true, false],
            ['primary', Column::TYPE_BIGINTEGER, false, false],
        ];
    }

    /**
     * @dataProvider dataPrimary
     */
    public function testPrimary($name, $type, $identity = true, $autoIncrement = true)
    {
        $this->invokeStaticMethod(Model::class, 'primary', [$name, $type, $identity, $autoIncrement]);

        $this->assertColumnAdded($name, $type, Column::BIND_PARAM_INT, true);

        $meta = $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class];

        $this->assertEquals([$name], $meta[MetaData::MODELS_PRIMARY_KEY]);
        $this->assertEquals([$name], $meta[MetaData::MODELS_NOT_NULL]);

        if ($identity) {
            $this->assertEquals($name, $meta[MetaData::MODELS_IDENTITY_COLUMN]);
        } else {
            $this->assertArrayNotHasKey(MetaData::MODELS_IDENTITY_COLUMN, $meta);
        }
        if ($autoIncrement) {
            $this->assertEquals([$name => true], $meta[MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT]);
        } else {
            $this->assertArrayNotHasKey(MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT, $meta);
        }
    }

    public function testMultipePrimary()
    {
        $this->invokeStaticMethod(Model::class, 'primary', ['p_1', Column::TYPE_BIGINTEGER, false, false]);
        $this->invokeStaticMethod(Model::class, 'primary', ['p_2', Column::TYPE_VARCHAR, false, false]);

        $this->assertColumnAdded('p_1', Column::TYPE_BIGINTEGER, Column::BIND_PARAM_INT, true);
        $this->assertColumnAdded('p_2', Column::TYPE_VARCHAR, Column::BIND_PARAM_STR, false);

        $meta = $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class];

        $this->assertEquals(['p_1', 'p_2'], $meta[MetaData::MODELS_PRIMARY_KEY]);
        $this->assertEquals(['p_1', 'p_2'], $meta[MetaData::MODELS_NOT_NULL]);
    }
    
    public function testInitialize()
    {
        $expected = [
            MetaData::MODELS_ATTRIBUTES               => [],
            MetaData::MODELS_PRIMARY_KEY              => [],
            MetaData::MODELS_NON_PRIMARY_KEY          => [],
            MetaData::MODELS_NOT_NULL                 => [],
            MetaData::MODELS_DATA_TYPES               => [],
            MetaData::MODELS_DATA_TYPES_NUMERIC       => [],
            MetaData::MODELS_DATE_AT                  => [],
            MetaData::MODELS_DATE_IN                  => [],
            MetaData::MODELS_IDENTITY_COLUMN          => false,
            MetaData::MODELS_DATA_TYPES_BIND          => [],
            MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => [],
            MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => [],
            MetaData::MODELS_DEFAULT_VALUES           => [],
            MetaData::MODELS_EMPTY_STRING_VALUES      => []
        ];

        $this->invokeStaticMethod(Model::class, 'initializeMetaData', []);

        $this->assertEquals($expected, $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class]);
    }
}
