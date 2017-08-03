<?php

namespace Test\Database\Schema;

use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema;
use Neutrino\Support\Fluent;
use Neutrino\Support\Reflacker;
use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;

class BlueprintTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $blueprint = new Blueprint('table');

        $this->assertEquals('table', $blueprint->getTable());
    }

    /**
     * @return array
     */
    public function dataIncrements()
    {
        return [
            ['increments', 'col_increments', [],
             new Fluent(['name' => 'col_increments', 'type' => 'integer', 'autoIncrement' => true, 'unsigned' => true]),
             new Column('col_increments', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'notNull' => true])],
            ['tinyIncrements', 'col_tinyIncrements', [],
             new Fluent(['name' => 'col_tinyIncrements', 'type' => 'tinyInteger', 'autoIncrement' => true, 'unsigned' => true]),
             new Column('col_tinyIncrements', ['type' => 'TINYINT', 'typeReference' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'notNull' => true])],
            ['smallIncrements', 'col_smallIncrements', [],
             new Fluent(['name' => 'col_smallIncrements', 'type' => 'smallInteger', 'autoIncrement' => true, 'unsigned' => true]),
             new Column('col_smallIncrements', ['type' => 'SMALLINT', 'typeReference' => Column::TYPE_INTEGER,  'autoIncrement' => true, 'unsigned' => true, 'notNull' => true])],
            ['mediumIncrements', 'col_mediumIncrements', [],
             new Fluent(['name' => 'col_mediumIncrements', 'type' => 'mediumInteger', 'autoIncrement' => true, 'unsigned' => true]),
             new Column('col_mediumIncrements', ['type' => 'MEDIUMINT',  'typeReference' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'notNull' => true])],
            ['bigIncrements', 'col_bigIncrements', [],
             new Fluent(['name' => 'col_bigIncrements', 'type' => 'bigInteger', 'autoIncrement' => true, 'unsigned' => true]),
             new Column('col_bigIncrements', ['type' => Column::TYPE_BIGINTEGER, 'autoIncrement' => true, 'unsigned' => true, 'notNull' => true])],
        ];
    }

    /**
     * @return array
     */
    public function dataStrings()
    {
        return [
            ['char', 'col_char', [],
             new Fluent(['name' => 'col_char', 'type' => 'char', 'size' => 255]),
             new Column('col_char', ['type' => Column::TYPE_CHAR, 'size' => 255, 'notNull' => true])],
            ['char', 'col_char_4', [4],
             new Fluent(['name' => 'col_char_4', 'type' => 'char', 'size' => 4]),
             new Column('col_char_4', ['type' => Column::TYPE_CHAR, 'size' => 4, 'notNull' => true])],
            ['string', 'col_string', [],
             new Fluent(['name' => 'col_string', 'type' => 'string', 'size' => 255]),
             new Column('col_string', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true])],
            ['string', 'col_string', [128],
             new Fluent(['name' => 'col_string', 'type' => 'string', 'size' => 128]),
             new Column('col_string', ['type' => Column::TYPE_VARCHAR, 'size' => 128, 'notNull' => true])],
            ['text', 'col_text', [],
             new Fluent(['name' => 'col_text', 'type' => 'text',]),
             new Column('col_text', ['type' => Column::TYPE_TEXT, 'notNull' => true])],
            ['mediumText', 'col_mediumText', [],
             new Fluent(['name' => 'col_mediumText', 'type' => 'mediumText',]),
             new Column('col_mediumText', ['type' => 'MEDIUMTEXT', 'typeReference' => Column::TYPE_TEXT, 'notNull' => true])],
            ['longText', 'col_longText', [],
             new Fluent(['name' => 'col_longText', 'type' => 'longText',]),
             new Column('col_longText', ['type' => 'LONGTEXT', 'typeReference' => Column::TYPE_TEXT, 'notNull' => true])],
        ];
    }

    /**
     * @return array
     */
    public function dataIntegers()
    {
        return [
            ['integer', 'col_integer', [false, false],
             new Fluent(['name' => 'col_integer', 'type' => 'integer', 'autoIncrement' => false, 'unsigned' => false]),
             new Column('col_integer', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => false, 'notNull' => true])],
            ['integer', 'col_integer_inc', [true, false],
             new Fluent(['name' => 'col_integer_inc', 'type' => 'integer', 'autoIncrement' => true, 'unsigned' => false]),
             new Column('col_integer_inc', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => false, 'notNull' => true])],
            ['integer', 'col_integer_inc_uns', [true, true],
             new Fluent(['name' => 'col_integer_inc_uns', 'type' => 'integer', 'autoIncrement' => true, 'unsigned' => true]),
             new Column('col_integer_inc_uns', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'notNull' => true])],
            ['integer', 'col_integer_2', [],
             new Fluent(['name' => 'col_integer_2', 'type' => 'integer', 'autoIncrement' => false, 'unsigned' => false]),
             new Column('col_integer_2', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => false, 'notNull' => true])],
            ['tinyInteger', 'col_tinyInteger', [],
             new Fluent(['name' => 'col_tinyInteger', 'type' => 'tinyInteger', 'autoIncrement' => false, 'unsigned' => false]),
             new Column('col_tinyInteger', ['type' => 'TINYINT', 'typeReference' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => false, 'notNull' => true])],
            ['smallInteger', 'col_smallInteger', [],
             new Fluent(['name' => 'col_smallInteger', 'type' => 'smallInteger', 'autoIncrement' => false, 'unsigned' => false]),
             new Column('col_smallInteger', ['type' => 'SMALLINT', 'typeReference' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => false, 'notNull' => true])],
            ['mediumInteger', 'col_mediumInteger', [],
             new Fluent(['name' => 'col_mediumInteger', 'type' => 'mediumInteger', 'autoIncrement' => false, 'unsigned' => false]),
             new Column('col_mediumInteger', ['type' => 'MEDIUMINT', 'typeReference' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => false, 'notNull' => true])],
            ['bigInteger', 'col_bigInteger', [],
             new Fluent(['name' => 'col_bigInteger', 'type' => 'bigInteger', 'autoIncrement' => false, 'unsigned' => false,]),
             new Column('col_bigInteger', ['type' => Column::TYPE_BIGINTEGER, 'autoIncrement' => false, 'unsigned' => false, 'notNull' => true])],
            ['unsignedInteger', 'col_unsignedInteger', [],
             new Fluent(['name' => 'col_unsignedInteger', 'type' => 'integer', 'autoIncrement' => false, 'unsigned' => true]),
             new Column('col_unsignedInteger', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => true, 'notNull' => true])],
            ['unsignedTinyInteger', 'col_unsignedTinyInteger', [],
             new Fluent(['name' => 'col_unsignedTinyInteger', 'type' => 'tinyInteger', 'autoIncrement' => false, 'unsigned' => true]),
             new Column('col_unsignedTinyInteger', ['type' => 'TINYINT', 'typeReference' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => true, 'notNull' => true])],
            ['unsignedSmallInteger', 'col_unsignedSmallInteger', [],
             new Fluent(['name' => 'col_unsignedSmallInteger', 'type' => 'smallInteger', 'autoIncrement' => false, 'unsigned' => true]),
             new Column('col_unsignedSmallInteger', ['type' => 'SMALLINT', 'typeReference' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => true, 'notNull' => true])],
            ['unsignedMediumInteger', 'col_unsignedMediumInteger', [],
             new Fluent(['name' => 'col_unsignedMediumInteger', 'type' => 'mediumInteger', 'autoIncrement' => false, 'unsigned' => true]),
             new Column('col_unsignedMediumInteger', ['type' => 'MEDIUMINT', 'typeReference' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => true, 'notNull' => true])],
            ['unsignedBigInteger', 'col_unsignedBigInteger', [],
             new Fluent(['name' => 'col_unsignedBigInteger', 'type' => 'bigInteger', 'autoIncrement' => false, 'unsigned' => true]),
             new Column('col_unsignedBigInteger', ['type' => Column::TYPE_BIGINTEGER, 'autoIncrement' => false, 'unsigned' => true, 'notNull' => true])],
        ];
    }

    /**
     * @return array
     */
    public function dataDecimal()
    {
        return [
            ['float', 'col_float', [],
             new Fluent(['name' => 'col_float', 'type' => 'float',]),
             new Column('col_float', ['type' => Column::TYPE_FLOAT, 'notNull' => true])],
            ['double', 'col_double', [],
             new Fluent(['name' => 'col_double', 'type' => 'double',]),
             new Column('col_double', ['type' => Column::TYPE_DOUBLE, 'notNull' => true])],
            ['decimal', 'col_decimal', [],
             new Fluent(['name' => 'col_decimal', 'type' => 'decimal',]),
             new Column('col_decimal', ['type' => Column::TYPE_DECIMAL, 'notNull' => true])],
        ];
    }

    /**
     * @return array
     */
    public function dataOthers()
    {
        return [
            ['boolean', 'col_boolean', [],
             new Fluent(['name' => 'col_boolean', 'type' => 'boolean',]),
             new Column('col_boolean', ['type' => Column::TYPE_BOOLEAN, 'notNull' => true])],
            ['enum', 'col_enum', [['a', 'b', 'c']],
             new Fluent(['name' => 'col_enum', 'type' => 'enum', 'values' => ['a', 'b', 'c']]),
             new Column('col_enum', ['type' => 'ENUM', 'typeReference' => -1, 'typeValues' => ['a', 'b', 'c'], 'notNull' => true])],
            ['json', 'col_json', [],
             new Fluent(['name' => 'col_json', 'type' => 'json',]),
             new Column('col_json', ['type' => Column::TYPE_JSON, 'notNull' => true])],
            ['jsonb', 'col_jsonb', [],
             new Fluent(['name' => 'col_jsonb', 'type' => 'jsonb',]),
             new Column('col_jsonb', ['type' => Column::TYPE_JSONB, 'notNull' => true])],
            ['date', 'col_date', [],
             new Fluent(['name' => 'col_date', 'type' => 'date',]),
             new Column('col_date', ['type' => Column::TYPE_DATE, 'notNull' => true])],
            ['dateTime', 'col_dateTime', [],
             new Fluent(['name' => 'col_dateTime', 'type' => 'dateTime', 'precision' => 0]),
             new Column('col_dateTime', ['type' => Column::TYPE_DATETIME, 'notNull' => true])],
            ['dateTimeTz', 'col_dateTimeTz', [],
             new Fluent(['name' => 'col_dateTimeTz', 'type' => 'dateTimeTz', 'precision' => 0]),
             new Column('col_dateTimeTz', ['type' => Column::TYPE_DATETIME, 'notNull' => true])],
            ['time', 'col_time', [],
             new Fluent(['name' => 'col_time', 'type' => 'time',]),
             new Column('col_time', ['type' => 'TIME', 'typeReference' => Column::TYPE_DATETIME, 'notNull' => true,])],
            ['timeTz', 'col_timeTz', [],
             new Fluent(['name' => 'col_timeTz', 'type' => 'timeTz',]),
             new Column('col_timeTz', ['type' => 'TIME', 'typeReference' => Column::TYPE_DATETIME, 'notNull' => true,])],
            ['timestamp', 'col_timestamp', [],
             new Fluent(['name' => 'col_timestamp', 'type' => 'timestamp', 'precision' => 0]),
             new Column('col_timestamp', ['type' => Column::TYPE_TIMESTAMP, 'precision' => 0, 'notNull' => true])],
            ['timestampTz', 'col_timestampTz', [],
             new Fluent(['name' => 'col_timestampTz', 'type' => 'timestampTz', 'precision' => 0]),
             new Column('col_timestampTz', ['type' => Column::TYPE_TIMESTAMP, 'precision' => 0, 'notNull' => true])],
            ['softDeletes', 'deleted_at', [],
             new Fluent(['name' => 'deleted_at', 'type' => 'timestamp', 'precision' => 0, 'nullable' => true]),
             new Column('deleted_at', ['type' => Column::TYPE_TIMESTAMP, 'precision' => 0, 'notNull' => false])],
            ['softDeletesTz', 'deleted_at', [],
             new Fluent(['name' => 'deleted_at', 'type' => 'timestampTz', 'precision' => 0, 'nullable' => true]),
             new Column('deleted_at', ['type' => Column::TYPE_TIMESTAMP,  'precision' => 0, 'notNull' => false])],
            ['binary', 'col_binary', [],
             new Fluent(['name' => 'col_binary', 'type' => 'blob',]),
             new Column('col_binary', ['type' => Column::TYPE_BLOB, 'notNull' => true,])],
            ['blob', 'col_blob', [],
             new Fluent(['name' => 'col_blob', 'type' => 'blob',]),
             new Column('col_blob', ['type' => Column::TYPE_BLOB, 'notNull' => true,])],
            ['tinyBlob', 'col_tinyBlob', [],
             new Fluent(['name' => 'col_tinyBlob', 'type' => 'tinyBlob',]),
             new Column('col_tinyBlob', ['type' => Column::TYPE_TINYBLOB, 'notNull' => true,])],
            ['mediumBlob', 'col_mediumBlob', [],
             new Fluent(['name' => 'col_mediumBlob', 'type' => 'mediumBlob',]),
             new Column('col_mediumBlob', ['type' => Column::TYPE_MEDIUMBLOB, 'notNull' => true,])],
            ['longBlob', 'col_longBlob', [],
             new Fluent(['name' => 'col_longBlob', 'type' => 'longBlob',]),
             new Column('col_longBlob', ['type' => Column::TYPE_LONGBLOB, 'notNull' => true,])],
            ['uuid', 'col_uuid', [],
             new Fluent(['name' => 'col_uuid', 'type' => 'uuid']),
             new Column('col_uuid', ['type' => Column::TYPE_CHAR, 'size' => 36, 'notNull' => true])],
            ['ipAddress', 'col_ipAddress', [],
             new Fluent(['name' => 'col_ipAddress', 'type' => 'ipAddress']),
             new Column('col_ipAddress', ['type' => Column::TYPE_VARCHAR, 'size' => 45, 'notNull' => true])],
            ['macAddress', 'col_macAddress', [],
             new Fluent(['name' => 'col_macAddress', 'type' => 'macAddress']),
             new Column('col_macAddress', ['type' => Column::TYPE_VARCHAR, 'size' => 17, 'notNull' => true])],
            ['rememberToken', 'remember_token', [],
             new Fluent(['name' => 'remember_token', 'type' => 'string', 'size' => 100, 'nullable' => true]),
             new Column('remember_token', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'notNull' => false])],
        ];
    }

    /**
     * @return array
     */
    public function dataColumns()
    {
        return array_merge($this->dataIntegers(), $this->dataDecimal(), $this->dataIncrements(), $this->dataStrings(), $this->dataOthers());
    }

    /**
     * @return array
     */
    public function dataGroupColums()
    {
        return array_combine(['integers', 'decimal', 'increments', 'strings', 'others'], [
            [$this->dataIntegers()],
            [$this->dataDecimal()],
            [$this->dataIncrements()],
            [$this->dataStrings()],
            [$this->dataOthers()]
        ]);
    }

    /**
     * @dataProvider dataColumns
     *
     * @param $type
     * @param $name
     * @param $parameters
     * @param $excepted
     */
    public function testColumn($type, $name, $parameters, $excepted)
    {
        $blueprint = new Blueprint('table');

        $this->assertEquals($excepted, $blueprint->$type($name, ...$parameters));

        $this->assertEquals([$name => $excepted], $blueprint->getColumns());
    }

    /**
     * @dataProvider dataGroupColums
     *
     * @param $columns
     */
    public function testColumns($columns)
    {
        $blueprint = new Blueprint('table');
        $watch     = [];
        foreach ($columns as $column) {
            $type       = $column[0];
            $name       = $column[1];
            $parameters = $column[2];
            $watch[$name]    = $excepted = $column[3];
            $this->assertEquals($excepted, $blueprint->$type($name, ...$parameters));
        }

        $this->assertEquals($watch, $blueprint->getColumns());
    }

    public function dataTimestamps()
    {
        return [
            ['timestamps', [
                'created_at' => new Fluent(['name' => 'created_at', 'type' => 'timestamp', 'precision' => 0, 'default' => 'CURRENT_TIMESTAMP']),
                'updated_at' => new Fluent(['name' => 'updated_at', 'type' => 'timestamp', 'precision' => 0, 'default' => 'CURRENT_TIMESTAMP', 'onUpdate' =>  'CURRENT_TIMESTAMP'])
            ]],
            ['timestampsTz', [
                'created_at' => new Fluent(['name' => 'created_at', 'type' => 'timestampTz', 'precision' => 0, 'default' => 'CURRENT_TIMESTAMP']),
                'updated_at' => new Fluent(['name' => 'updated_at', 'type' => 'timestampTz', 'precision' => 0, 'default' => 'CURRENT_TIMESTAMP', 'onUpdate' =>  'CURRENT_TIMESTAMP'])
            ]],
            ['nullableTimestamps', [
                'created_at' => new Fluent(['name' => 'created_at', 'type' => 'timestamp', 'precision' => 0, 'default' => 'CURRENT_TIMESTAMP', 'nullable' => true]),
                'updated_at' => new Fluent(['name' => 'updated_at', 'type' => 'timestamp', 'precision' => 0, 'default' => 'CURRENT_TIMESTAMP', 'onUpdate' =>  'CURRENT_TIMESTAMP', 'nullable' => true])
            ]],
            ['nullableTimestampsTz', [
                'created_at' => new Fluent(['name' => 'created_at', 'type' => 'timestampTz', 'precision' => 0, 'default' => 'CURRENT_TIMESTAMP', 'nullable' => true]),
                'updated_at' => new Fluent(['name' => 'updated_at', 'type' => 'timestampTz', 'precision' => 0, 'default' => 'CURRENT_TIMESTAMP', 'onUpdate' =>  'CURRENT_TIMESTAMP', 'nullable' => true])
            ]],
        ];
    }

    /**
     * @dataProvider dataTimestamps
     */
    public function testTimestamps($func, $columns)
    {
        $blueprint = new Blueprint('test');

        $blueprint->$func();

        $this->assertEquals($columns, $blueprint->getColumns());
    }

    public function testMorphs()
    {
        $morphsId   = new Fluent(['name' => 'morphs_id', 'type' => 'integer', 'autoIncrement' => false, 'unsigned' => true]);
        $morphsType = new Fluent(['name' => 'morphs_type', 'type' => 'string', 'size' => 255]);
        $morphsIndex = new Fluent(['name' => 'test_morphs_id_morphs_type_index', 'columns' => ['morphs_id', 'morphs_type'], 'type' => 'index']);

        $nullMorphsId = new Fluent(['name' => 'null_morphs_id', 'type' => 'integer', 'autoIncrement' => false,'unsigned' => true, 'nullable' => true]);
        $nullMorphsType = new Fluent(['name' => 'null_morphs_type', 'type' => 'string', 'size' => 255, 'nullable' => true]);
        $nullMorphsIndex = new Fluent(['name' => 'test_null_morphs_id_null_morphs_type_index', 'columns' => ['null_morphs_id', 'null_morphs_type'], 'type' => 'index']);

        $blueprint = new Blueprint('test');

        $blueprint->morphs('morphs');

        $this->assertEquals([
            'morphs_id'   => $morphsId,
            'morphs_type' => $morphsType
        ], $blueprint->getColumns());

        $this->assertEquals([$morphsIndex], $blueprint->getIndexes());

        $blueprint->nullableMorphs('null_morphs');

        $this->assertEquals([
            'morphs_id'   => $morphsId,
            'morphs_type' => $morphsType,
            'null_morphs_id'   => $nullMorphsId,
            'null_morphs_type' => $nullMorphsType
        ], $blueprint->getColumns());

        $this->assertEquals([$morphsIndex, $nullMorphsIndex], $blueprint->getIndexes());
    }

    public function testOption()
    {
        $blueprint = new Blueprint('test');

        $blueprint->temporary();
        $blueprint->option('engine', 'InnoDB');

        $this->assertEquals([
            'temporary' => true,
            'engine'    => 'InnoDB'
        ], $blueprint->getOptions());
    }

    /**
     * @dataProvider dataColumns
     */
    public function testFluentToColumn($type, $name, $parameters, $fluent, $column)
    {
        $blueprint = new Blueprint('test');

        $actual = Reflacker::invoke($blueprint, 'fluentToColumn', $fluent, $this->getMockForAbstractClass(MockDialect::class));

        $this->assertEquals($column, $actual);
    }

    public function dataFluentToColumnIndexed()
    {
        return [
            [
                [new Fluent(['name' => 'column', 'type' => 'integer', 'index' => true])],
                [new Column('column', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
                [new Fluent(['name' => 'test_column_index', 'type' => 'index', 'columns' => ['column']])]
            ],
            [
                [new Fluent(['name' => 'column', 'type' => 'integer', 'unique' => true])],
                [new Column('column', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
                [new Fluent(['name' => 'test_column_unique', 'type' => 'unique', 'columns' => ['column']])]
            ],
        ];
    }

    /**
     * @dataProvider dataFluentToColumnIndexed
     */
    public function testFluentToColumnIndexed($columns, $expectedColumns, $expectedIndexes)
    {
        $blueprint = new Blueprint('test');

        $actualColumns = [];
        foreach ($columns as $column) {
            $actualColumns[] = Reflacker::invoke($blueprint, 'fluentToColumn', $column, $this->getMockForAbstractClass(MockDialect::class));
        }

        $this->assertEquals($expectedColumns, $actualColumns);
        $this->assertEquals($expectedIndexes, $blueprint->getIndexes());
    }

    public function testFluentToColumnReferenced()
    {
        $column = new Fluent([
            'name'       => 'column',
            'type'       => 'integer',
            'foreign'    => true,
            'on'         => 'test_2',
            'references' => ['column']
        ]);

        $expectedColumn = new Column('column', [
            'type'    => Column::TYPE_INTEGER,
            'notNull' => true
        ]);

        $expectedReference = new Fluent([
            'name'       => null,
            'type'       => 'foreign',
            'columns'    => ['column'],
            'on'         => 'test_2',
            'references' => ['column']
        ]);

        $blueprint = new Blueprint('test');

        $actualColumn = Reflacker::invoke($blueprint, 'fluentToColumn', $column, $this->getMockForAbstractClass(MockDialect::class));

        $this->assertEquals($expectedColumn, $actualColumn);
        $this->assertEquals([$expectedReference], $blueprint->getReferences());
    }

    public function dataFluentToIndex(){
        return [
            [
                new Fluent(['name' => 'test_column_index', 'type' => 'index', 'columns' => ['column']]),
                new Index('test_column_index', ['column'], 'index')
            ], [
                new Fluent(['name' => 'test_column_unique', 'type' => 'unique', 'columns' => ['column']]),
                new Index('test_column_unique', ['column'], 'unique')
            ], [
                new Fluent(['name' => 'test_column_primary', 'type' => 'primary', 'columns' => ['column']]),
                new Index('test_column_primary', ['column'], 'primary')
            ],
            [
                new Fluent(['name' => 'test_column_column_2_index', 'type' => 'index', 'columns' => ['column', 'column_2']]),
                new Index('test_column_column_2_index', ['column', 'column_2'], 'index')
            ], [
                new Fluent(['name' => 'test_column_column_2_unique', 'type' => 'unique', 'columns' => ['column', 'column_2']]),
                new Index('test_column_column_2_unique', ['column', 'column_2'], 'unique')
            ], [
                new Fluent(['name' => 'test_column_column_2_primary', 'type' => 'primary', 'columns' => ['column', 'column_2']]),
                new Index('test_column_column_2_primary', ['column', 'column_2'], 'primary')
            ],
        ];
    }

    /**
     * @dataProvider dataFluentToIndex
     */
    public function testFluentToIndex($fluent, $index)
    {
        $blueprint = new Blueprint('test');

        $actual = Reflacker::invoke($blueprint, 'fluentToIndex', $fluent, $this->getMockForAbstractClass(MockDialect::class));

        $this->assertEquals($index, $actual);
    }

    public function dataFluentToReference()
    {
        return [
            [
                new Fluent(['name'       => null, 'type' => 'foreign', 'columns' => ['column'], 'on' => 'test_2',
                            'references' => ['column']]),
                new Reference('test_column_foreign_test_2_column', [
                    'columns'           => ['column'],
                    'referencedTable'   => 'test_2',
                    'referencedColumns' => ['column'],
                ])
            ], [
                new Fluent(['name'       => 'test_column_foreign_test_2_column', 'type' => 'foreign', 'columns' => ['column'],
                            'on'         => 'test_2',
                            'references' => ['column']]),
                new Reference('test_column_foreign_test_2_column', [
                    'columns'           => ['column'],
                    'referencedTable'   => 'test_2',
                    'referencedColumns' => ['column'],
                ])
            ], [
                new Fluent(['name'       => null, 'type' => 'foreign', 'columns' => ['col_1', 'col_2'],
                            'on'         => 'test_2',
                            'references' => ['col_a', 'col_b']]),
                new Reference('test_col_1_col_2_foreign_test_2_col_a_col_b', [
                    'columns'           => ['col_1', 'col_2'],
                    'referencedTable'   => 'test_2',
                    'referencedColumns' => ['col_a', 'col_b'],
                ])
            ],
        ];
    }

    /**
     * @dataProvider dataFluentToReference
     */
    public function testFluentToReference($fluent, $reference)
    {
        $blueprint = new Blueprint('test');

        $actual = Reflacker::invoke($blueprint, 'fluentToReference', $fluent, $this->getMockForAbstractClass(MockDialect::class));

        $this->assertEquals($reference, $actual);
    }

    public function dataBuildTableDefinition()
    {
        $blueprint = new Blueprint('test');
        $blueprint->increments('id')->primary();
        $blueprint->string('name');
        $blueprint->string('email')->unique();

        $data[] = [$blueprint, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'primary' => true, 'notNull' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('email', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
            ],
            'indexes' => [
                new Index('test_email_unique', ['email'], 'unique')
            ],
        ]];

        $blueprint = new Blueprint('test');
        $blueprint->string('firstname')->primary();
        $blueprint->string('lastname')->primary();
        $blueprint->string('email')->unique();

        $data[] = [$blueprint, [
            'columns' => [
                new Column('firstname', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('lastname', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('email', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
            ],
            'indexes' => [
                new Index('test_firstname_lastname_primary', ['firstname', 'lastname'], 'primary'),
                new Index('test_email_unique', ['email'], 'unique'),
            ],
        ]];

        $blueprint = new Blueprint('test');
        $blueprint->string('firstname');
        $blueprint->string('lastname')->primary();
        $blueprint->string('email')->unique();
        $blueprint->primary('firstname');

        $data[] = [$blueprint, [
            'columns' => [
                new Column('firstname', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('lastname', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('email', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
            ],
            'indexes' => [
                new Index('test_firstname_lastname_primary', ['firstname', 'lastname'], 'primary'),
                new Index('test_email_unique', ['email'], 'unique'),
            ],
        ]];

        $blueprint = new Blueprint('test');
        $blueprint->string('firstname');
        $blueprint->string('lastname');
        $blueprint->string('email')->unique();
        $blueprint->primary(['firstname', 'lastname']);

        $data[] = [$blueprint, [
            'columns' => [
                new Column('firstname', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('lastname', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('email', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
            ],
            'indexes' => [
                new Index('test_firstname_lastname_primary', ['firstname', 'lastname'], 'primary'),
                new Index('test_email_unique', ['email'], 'unique'),
            ],
        ]];

        $blueprint = new Blueprint('test');
        $blueprint->increments('id')->primary();
        $blueprint->string('name');
        $blueprint->string('email')->unique();
        $blueprint->primary('id');

        $data[] = [$blueprint, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'primary' => true, 'notNull' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('email', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
            ],
            'indexes' => [
                new Index('test_email_unique', ['email'], 'unique')
            ],
        ]];

        $blueprint = new Blueprint('test');
        $blueprint->increments('id')->primary();
        $blueprint->string('name');
        $blueprint->string('email')->unique();
        $blueprint->string('sector')->foreign()->on('sector')->references('id');

        $data[] = [$blueprint, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'primary' => true, 'notNull' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('email', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('sector', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
            ],
            'indexes' => [
                new Index('test_email_unique', ['email'], 'unique')
            ],
            'references' => [
                new Reference('test_sector_foreign_sector_id', [
                    'columns'          => ['sector'],
                    'referencedTable'   => 'sector',
                    'referencedColumns' => ['id']
                ])
            ],
        ]];

        $blueprint = new Blueprint('test');
        $blueprint->increments('id')->primary();
        $blueprint->string('name');
        $blueprint->string('email')->unique();
        $blueprint->string('sector');
        $blueprint->foreign('sector')->on('sector')->references('id');

        $data[] = [$blueprint, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'primary' => true, 'notNull' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('email', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('sector', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
            ],
            'indexes' => [
                new Index('test_email_unique', ['email'], 'unique')
            ],
            'references' => [
                new Reference('test_sector_foreign_sector_id', [
                    'columns'          => ['sector'],
                    'referencedTable'   => 'sector',
                    'referencedColumns' => ['id']
                ])
            ],
        ]];

        $blueprint = new Blueprint('test');
        $blueprint->increments('id')->primary();
        $blueprint->string('name');
        $blueprint->string('email')->unique();
        $blueprint->integer('sector');
        $blueprint->string('department');
        $blueprint->foreign(['sector', 'department'])->on('sector')->references(['id', 'department']);

        $data[] = [$blueprint, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'primary' => true, 'notNull' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('email', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('sector', ['type' => Column::TYPE_INTEGER, 'notNull' => true]),
                new Column('department', ['type' => Column::TYPE_VARCHAR, 'size' => 255,  'notNull' => true]),
            ],
            'indexes' => [
                new Index('test_email_unique', ['email'], 'unique')
            ],
            'references' => [
                new Reference('test_sector_department_foreign_sector_id_department', [
                    'columns'          => ['sector', 'department'],
                    'referencedTable'   => 'sector',
                    'referencedColumns' => ['id', 'department']
                ])
            ],
        ]];

        $blueprint = new Blueprint('test');
        $blueprint->increments('id')->primary();
        $blueprint->string('name');
        $blueprint->string('email')->unique();
        $blueprint->integer('sector');
        $blueprint->string('department');
        $blueprint->foreign(['sector', 'department'])->on('sector')->references(['id', 'department']);
        $blueprint->temporary();
        $blueprint->option('engine', 'InnoDB');

        $data[] = [$blueprint, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true, 'primary' => true, 'notNull' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('email', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => true]),
                new Column('sector', ['type' => Column::TYPE_INTEGER, 'notNull' => true]),
                new Column('department', ['type' => Column::TYPE_VARCHAR, 'size' => 255,  'notNull' => true]),
            ],
            'indexes' => [
                new Index('test_email_unique', ['email'], 'unique')
            ],
            'references' => [
                new Reference('test_sector_department_foreign_sector_id_department', [
                    'columns'          => ['sector', 'department'],
                    'referencedTable'   => 'sector',
                    'referencedColumns' => ['id', 'department']
                ])
            ],
            'options' => [
                'TEMPORARY' => true,
                'ENGINE' => 'InnoDB'
            ]
        ]];

        return $data;
    }

    /**
     * @dataProvider dataBuildTableDefinition
     */
    public function testBuildTableDefinition(Blueprint $blueprint, $expected)
    {
        $actual = Reflacker::invoke($blueprint, 'buildTableDefinition', $this->getMockForAbstractClass(MockDialect::class));

        $this->assertEquals($expected, $actual);
    }

    public function dataFuncCommand()
    {
        return [
            ['dropColumn', ['column'], [
                new Fluent(['name' => 'dropColumn', 'column' => 'column'])]],
            ['dropColumns', [['column', 'col']], [
                new Fluent(['name' => 'dropColumn', 'column' => 'column']),
                new Fluent(['name' => 'dropColumn', 'column' => 'col'])]],
            ['dropIndex', ['index'], [
                new Fluent(['name' => 'dropIndex', 'index' => 'index'])]],
            ['dropUnique', ['unique'], [
                new Fluent(['name' => 'dropIndex', 'index' => 'unique'])]],
            ['dropPrimary', [], [
                new Fluent(['name' => 'dropPrimary'])]],
            ['dropForeign', ['fk'], [
                new Fluent(['name' => 'dropForeign', 'reference' => 'fk'])]],
            ['rename', ['test_2'], [
                new Fluent(['name' => 'rename', 'to' => 'test_2'])]],
            ['renameColumn', ['column', 'col'], [
                new Fluent(['name' => 'renameColumn', 'from' => 'column', 'to' => 'col'])]],

            ['dropTimestamps', [], [
                new Fluent(['name' => 'dropColumn', 'column' => 'created_at']),
                new Fluent(['name' => 'dropColumn', 'column' => 'updated_at'])]],
            ['dropTimestampsTz', [], [
                new Fluent(['name' => 'dropColumn', 'column' => 'created_at']),
                new Fluent(['name' => 'dropColumn', 'column' => 'updated_at'])]],

            ['dropSoftDeletes', ['column'], [
                new Fluent(['name' => 'dropColumn', 'column' => 'deleted_at'])]],
            ['dropSoftDeletesTz', ['column'], [
                new Fluent(['name' => 'dropColumn', 'column' => 'deleted_at'])]],

            ['dropRememberToken', ['column'], [
                new Fluent(['name' => 'dropColumn', 'column' => 'remember_token'])]],
        ];
    }

    /**
     * @dataProvider dataFuncCommand
     */
    public function testFuncCommand($func, $params, $expecteds)
    {
        $blueprint = new Blueprint('test');

        $blueprint->$func(...$params);

        $this->assertEquals($expecteds, $blueprint->getCommands());
    }

    public function testAction()
    {
        $blueprint = new Blueprint('test');

        $blueprint->update();

        $this->assertEquals('update', Reflacker::get($blueprint, 'action'));

        $blueprint->create();

        $this->assertEquals('create', Reflacker::get($blueprint, 'action'));

        $blueprint->drop();

        $this->assertEquals('drop', Reflacker::get($blueprint, 'action'));

        $blueprint->dropIfExists();

        $this->assertEquals('dropIfExists', Reflacker::get($blueprint, 'action'));

    }

    public function dataBuildCommands()
    {
        $blueprint = new Blueprint('test');
        $blueprint->update();

        $blueprint->string('name');

        $data[] = [$blueprint, [], [
            new Fluent(['name' => 'addColumn', 'column' => new Fluent(['name' => 'name', 'type' => 'string', 'size' => 255])])
        ]];

        $blueprint = new Blueprint('test');
        $blueprint->update();

        $blueprint->string('name');

        $data[] = [
            $blueprint,
            [
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 128])
            ], [
                new Fluent([
                    'name'   => 'modifyColumn',
                    'column' => new Fluent(['name' => 'name', 'type' => 'string', 'size' => 255]),
                    'from'   => new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 128])
                ])
            ]
        ];

        $blueprint = new Blueprint('test');
        $blueprint->update();

        $blueprint->string('name')->unique();
        $blueprint->string('test')->index();

        $data[] = [
            $blueprint,
            [
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 128])
            ], [
                new Fluent([
                    'name'   => 'modifyColumn',
                    'column' => new Fluent(['name' => 'name', 'type' => 'string', 'size' => 255, 'unique' => true]),
                    'from'   => new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 128])
                ]),
                new Fluent(['name' => 'addColumn', 'column' => new Fluent(['name' => 'test', 'type' => 'string', 'size' => 255, 'index' => true])])
            ]
        ];

        $blueprint = new Blueprint('test');
        $blueprint->update();

        $blueprint->string('name');
        $blueprint->string('test');
        $blueprint->unique('name');
        $blueprint->index('test');

        $data[] = [
            $blueprint,
            [
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 128])
            ], [
                new Fluent([
                    'name'   => 'modifyColumn',
                    'column' => new Fluent(['name' => 'name', 'type' => 'string', 'size' => 255]),
                    'from'   => new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 128])
                ]),
                new Fluent(['name' => 'addColumn', 'column' => new Fluent(['name' => 'test', 'type' => 'string', 'size' => 255])]),
                new Fluent(['name' => 'addIndex', 'index' => new Fluent(['name' => 'test_name_unique', 'columns' => ['name'], 'type' => 'unique'])]),
                new Fluent(['name' => 'addIndex', 'index' => new Fluent(['name' => 'test_test_index', 'columns' => ['test'], 'type' => 'index'])]),
            ]
        ];

        $blueprint = new Blueprint('test');
        $blueprint->update();

        $blueprint->foreign('test')->on('test_2')->references('test');

        $data[] = [
            $blueprint,
            [
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 128])
            ], [
                new Fluent(['name' => 'addForeign', 'reference' => new Fluent([
                    'name'       => null,
                    'columns'    => ['test'],
                    'on'         => 'test_2',
                    'references' => 'test',
                    'type' => 'foreign'
                ])]),
            ]
        ];

        return $data;
    }

    /**
     * @dataProvider dataBuildCommands
     */
    public function testBuildCommands(Blueprint $blueprint, $describeColumnsMock, $expectedCommands)
    {
        $db = $this->createMock(\Phalcon\Db\Adapter::class);

        $db->expects($this->once())->method('describeColumns')->willReturn($describeColumnsMock);

        Reflacker::invoke($blueprint, 'buildCommands', $db, ['dbname' => 'schema']);

        $this->assertEquals($expectedCommands, $blueprint->getCommands());
    }
}

abstract class MockDialect implements Schema\DialectInterface
{
    use Schema\DialectTrait;
}
