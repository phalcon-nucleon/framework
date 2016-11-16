<?php

namespace Test\Models;

use Luxury\Model;
use Luxury\Support\Arr;
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
            [
                'test', Column::TYPE_BIGINTEGER,
                ['nullable' => false, 'default' => null, 'autoInsert' => false, 'autoUpdate' => false],
                Column::BIND_PARAM_INT, true
            ],
            [
                'test', Column::TYPE_INTEGER,
                ['nullable' => true, 'default' => null, 'autoInsert' => false, 'autoUpdate' => false],
                Column::BIND_PARAM_INT, true
            ],
            [
                'test', Column::TYPE_TIMESTAMP,
                ['nullable' => false, 'default' => 1, 'autoInsert' => false, 'autoUpdate' => false],
                Column::BIND_PARAM_INT, true
            ],
            [
                'test', Column::TYPE_DECIMAL,
                ['nullable' => true, 'default' => 1, 'autoInsert' => false, 'autoUpdate' => false],
                Column::BIND_PARAM_DECIMAL, true
            ],
            [
                'test', Column::TYPE_FLOAT,
                ['nullable' => false, 'default' => null, 'autoInsert' => true, 'autoUpdate' => false],
                Column::BIND_PARAM_DECIMAL, true
            ],
            [
                'test', Column::TYPE_DOUBLE,
                ['nullable' => true, 'default' => null, 'autoInsert' => true, 'autoUpdate' => false],
                Column::BIND_PARAM_DECIMAL, true
            ],
            [
                'test', Column::TYPE_JSON,
                ['nullable' => false, 'default' => 1, 'autoInsert' => true, 'autoUpdate' => false],
                Column::BIND_PARAM_STR
            ],
            [
                'test', Column::TYPE_TEXT,
                ['nullable' => true, 'default' => 1, 'autoInsert' => true, 'autoUpdate' => false],
                Column::BIND_PARAM_STR
            ],
            [
                'test', Column::TYPE_CHAR,
                ['nullable' => false, 'default' => null, 'autoInsert' => false, 'autoUpdate' => true],
                Column::BIND_PARAM_STR
            ],
            [
                'test', Column::TYPE_VARCHAR,
                ['nullable' => true, 'default' => null, 'autoInsert' => false, 'autoUpdate' => true],
                Column::BIND_PARAM_STR
            ],
            [
                'test', Column::TYPE_DATE,
                ['nullable' => false, 'default' => 1, 'autoInsert' => false, 'autoUpdate' => true],
                Column::BIND_PARAM_STR
            ],
            [
                'test', Column::TYPE_DATETIME,
                ['nullable' => true, 'default' => 1, 'autoInsert' => false, 'autoUpdate' => true],
                Column::BIND_PARAM_STR
            ],
            [
                'test', Column::TYPE_BLOB,
                ['nullable' => false, 'default' => null, 'autoInsert' => true, 'autoUpdate' => true],
                Column::BIND_PARAM_BLOB
            ],
            [
                'test', Column::TYPE_JSONB,
                ['nullable' => true, 'default' => null, 'autoInsert' => true, 'autoUpdate' => true],
                Column::BIND_PARAM_BLOB
            ],
            [
                'test', Column::TYPE_MEDIUMBLOB,
                ['nullable' => false, 'default' => 1, 'autoInsert' => true, 'autoUpdate' => true],
                Column::BIND_PARAM_BLOB
            ],
            [
                'test', Column::TYPE_TINYBLOB,
                ['nullable' => true, 'default' => 1, 'autoInsert' => true, 'autoUpdate' => true],
                Column::BIND_PARAM_BLOB
            ],
            [
                'test', Column::TYPE_LONGBLOB,
                [],
                Column::BIND_PARAM_BLOB
            ],
            [
                'test', Column::TYPE_BOOLEAN,
                [],
                Column::BIND_PARAM_BOOL
            ],
            [
                'test', null,
                [],
                Column::BIND_PARAM_NULL
            ],
            [
                'test', 'abc',
                [],
                Column::BIND_SKIP
            ],
        ];
    }

    /**
     * @dataProvider dataColumn
     */
    public function testColumn($name, $type, $options, $expectedBind, $numeric = false)
    {
        $this->invokeStaticMethod(Model::class, 'column', [$name, $type, $options]);

        $meta = $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class];

        $this->assertColumnAdded($name, $type, $expectedBind, $numeric);

        if (Arr::fetch($options, 'nullable')) {
            $this->assertArrayHasKey(MetaData::MODELS_EMPTY_STRING_VALUES, $meta);
            $this->assertEquals([$name => true], $meta[MetaData::MODELS_EMPTY_STRING_VALUES]);
        } else {
            $this->assertArrayHasKey(MetaData::MODELS_NOT_NULL, $meta);
            $this->assertEquals([$name], $meta[MetaData::MODELS_NOT_NULL]);
        }

        if (!is_null(Arr::fetch($options, 'default'))) {
            $this->assertArrayHasKey(MetaData::MODELS_DEFAULT_VALUES, $meta);
            $this->assertEquals([$name => Arr::fetch($options, 'default')], $meta[MetaData::MODELS_DEFAULT_VALUES]);
        } else {
            $this->assertArrayNotHasKey(MetaData::MODELS_DEFAULT_VALUES, $meta);
        }

        if (Arr::fetch($options, 'autoInsert')) {
            $this->assertArrayHasKey(MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT, $meta);
            $this->assertEquals([$name => true], $meta[MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT]);
        }

        if (Arr::fetch($options, 'autoUpdate')) {
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
        $options = [];
        $binds = [];
        foreach ($columns as $column) {
            $this->invokeStaticMethod(Model::class, 'column', $column);

            $name = array_shift($column);
            $attrs[] = $name;
            $names[$name] = $name;
            $types[$name] = array_shift($column);
            $options[$name] = array_shift($column);
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
            ['primary', Column::TYPE_BIGINTEGER, ['identity' => true, 'autoIncrement' => true]],
            ['primary', Column::TYPE_BIGINTEGER, ['identity' => false, 'autoIncrement' => true]],
            ['primary', Column::TYPE_BIGINTEGER, ['identity' => true, 'autoIncrement' => false]],
            ['primary', Column::TYPE_BIGINTEGER, ['identity' => false, 'autoIncrement' => false]],
        ];
    }

    /**
     * @dataProvider dataPrimary
     */
    public function testPrimary($name, $type, $options)
    {
        $this->invokeStaticMethod(Model::class, 'primary', [$name, $type, $options]);

        $this->assertColumnAdded($name, $type, Column::BIND_PARAM_INT, true);

        $meta = $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class];

        $this->assertEquals([$name], $meta[MetaData::MODELS_PRIMARY_KEY]);
        $this->assertEquals([$name], $meta[MetaData::MODELS_NOT_NULL]);

        if ($options['identity']) {
            $this->assertEquals($name, $meta[MetaData::MODELS_IDENTITY_COLUMN]);
        } else {
            $this->assertArrayNotHasKey(MetaData::MODELS_IDENTITY_COLUMN, $meta);
        }
        if ($options['autoIncrement']) {
            $this->assertEquals([$name => true], $meta[MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT]);
        } else {
            $this->assertArrayNotHasKey(MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT, $meta);
        }
    }

    public function testMultipePrimary()
    {
        $this->invokeStaticMethod(Model::class, 'primary', ['p_1', Column::TYPE_BIGINTEGER, ['multiple' => true]]);
        $this->invokeStaticMethod(Model::class, 'primary', ['p_2', Column::TYPE_VARCHAR, ['multiple' => true]]);

        $this->assertColumnAdded('p_1', Column::TYPE_BIGINTEGER, Column::BIND_PARAM_INT, true);
        $this->assertColumnAdded('p_2', Column::TYPE_VARCHAR, Column::BIND_PARAM_STR, false);

        $meta = $this->getStaticValueProperty(Model::class, 'metaDatasClass')[Model::class];

        $this->assertEquals(['p_1', 'p_2'], $meta[MetaData::MODELS_PRIMARY_KEY]);
        $this->assertEquals(['p_1', 'p_2'], $meta[MetaData::MODELS_NOT_NULL]);
    }
}
