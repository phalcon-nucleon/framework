<?php

namespace Test\Database\Schema;

use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Column;

class BlueprintTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $blueprint = new Blueprint('table');

        $this->assertEquals('table', $blueprint->getTable());
    }

    public function dataColumns()
    {
        return [
            ['integer', 'column', [false, false], new Column('column', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => false])],
            ['integer', 'column', [true, false], new Column('column', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => false])],
            ['integer', 'column', [true, true], new Column('column', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => true, 'unsigned' => true])],

            /* TODO Specific test
            ['increments', 'column', [], new Column('column', ['type' => Column::TYPE_INCREMENTS,])],
            ['tinyIncrements', 'column', [], new Column('column', ['type' => Column::TYPE_TINYINCREMENTS,])],
            ['smallIncrements', 'column', [], new Column('column', ['type' => Column::TYPE_SMALLINCREMENTS,])],
            ['mediumIncrements', 'column', [], new Column('column', ['type' => Column::TYPE_MEDIUMINCREMENTS,])],
            ['bigIncrements', 'column', [], new Column('column', ['type' => Column::TYPE_BIGINCREMENTS,])],*/

            ['char', 'column', [], new Column('column', ['type' => Column::TYPE_CHAR, 'size' => 255])],
            ['char', 'column', [4], new Column('column', ['type' => Column::TYPE_CHAR, 'size' => 4])],
            ['string', 'column', [], new Column('column', ['type' => Column::TYPE_VARCHAR, 'size' => 255])],
            ['string', 'column', [128], new Column('column', ['type' => Column::TYPE_VARCHAR, 'size' => 128])],
            ['text', 'column', [], new Column('column', ['type' => Column::TYPE_TEXT,])],
            //['mediumText', 'column', [], new Column('column', ['type' => Column::TYPE_MEDIUMBLOB,])],
            //['longText', 'column', [], new Column('column', ['type' => Column::TYPE_LONGBLOB,])],
            ['integer', 'column', [], new Column('column', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => false])],
            ['tinyInteger', 'column', [], new Column('column', ['type' => Column::TYPE_INTEGER, 'size' => 1, 'autoIncrement' => false, 'unsigned' => false])],
            ['smallInteger', 'column', [], new Column('column', ['type' => Column::TYPE_INTEGER, 'size' => 2, 'autoIncrement' => false, 'unsigned' => false])],
            ['mediumInteger', 'column', [], new Column('column', ['type' => Column::TYPE_INTEGER, 'size' => 3, 'autoIncrement' => false, 'unsigned' => false])],
            ['bigInteger', 'column', [], new Column('column', ['type' => Column::TYPE_BIGINTEGER, 'autoIncrement' => false, 'unsigned' => false,])],
            ['unsignedInteger', 'column', [], new Column('column', ['type' => Column::TYPE_INTEGER, 'autoIncrement' => false, 'unsigned' => true])],
            ['unsignedTinyInteger', 'column', [], new Column('column', ['type' => Column::TYPE_INTEGER, 'size' => 1, 'autoIncrement' => false, 'unsigned' => true])],
            ['unsignedSmallInteger', 'column', [], new Column('column', ['type' => Column::TYPE_INTEGER, 'size' => 2, 'autoIncrement' => false, 'unsigned' => true])],
            ['unsignedMediumInteger', 'column', [], new Column('column', ['type' => Column::TYPE_INTEGER, 'size' => 3, 'autoIncrement' => false, 'unsigned' => true])],
            ['unsignedBigInteger', 'column', [], new Column('column', ['type' => Column::TYPE_BIGINTEGER, 'autoIncrement' => false, 'unsigned' => true])],
            ['float', 'column', [], new Column('column', ['type' => Column::TYPE_FLOAT,])],
            ['double', 'column', [], new Column('column', ['type' => Column::TYPE_DOUBLE,])],
            ['decimal', 'column', [], new Column('column', ['type' => Column::TYPE_DECIMAL,])],
            ['boolean', 'column', [], new Column('column', ['type' => Column::TYPE_BOOLEAN,])],
            //['enum', 'column', [], new Column('column', ['type' => Column::TYPE_ENUM,])],
            ['json', 'column', [], new Column('column', ['type' => Column::TYPE_JSON,])],
            ['jsonb', 'column', [], new Column('column', ['type' => Column::TYPE_JSONB,])],
            ['date', 'column', [], new Column('column', ['type' => Column::TYPE_DATE,])],
            ['dateTime', 'column', [], new Column('column', ['type' => Column::TYPE_DATETIME,])],
            /* ['dateTimeTz', 'column', [], new Column('column', ['type' => Column::TYPE_DATETIME,])],
             ['time', 'column', [], new Column('column', ['type' => Column::TYPE_TIMESTAMP,])],
             ['timeTz', 'column', [], new Column('column', ['type' => Column::TYPE_TIMESTAMP,])],*/
            ['timestamp', 'column', [], new Column('column', ['type' => Column::TYPE_TIMESTAMP,])],
            /*['timestampTz', 'column', [], new Column('column', ['type' => Column::TYPE_TIMESTAMP,])],*/
            /* TODO Specific test
            ['timestamps', 'column', [], new Column('column', ['type' => Column::TYPE_TIMESTAMPS,])],
            ['nullableTimestamps', 'column', [], new Column('column', ['type' => Column::TYPE_NULLABLETIMESTAMPS,])],
            ['timestampsTz', 'column', [], new Column('column', ['type' => Column::TYPE_TIMESTAMPSTZ,])],
            ['softDeletes', 'column', [], new Column('column', ['type' => Column::TYPE_SOFTDELETES,])],
            ['softDeletesTz', 'column', [], new Column('column', ['type' => Column::TYPE_SOFTDELETESTZ,])],*/
            ['binary', 'column', [], new Column('column', ['type' => Column::TYPE_BLOB,])],
            ['uuid', 'column', [], new Column('column', ['type' => Column::TYPE_CHAR, 'size' => 36])],
            ['ipAddress', 'column', [], new Column('column', ['type' => Column::TYPE_VARCHAR, 'size' => 45])],
            ['macAddress', 'column', [], new Column('column', ['type' => Column::TYPE_VARCHAR, 'size' => 17])],
            /* TODO Specific test
            ['morphs', 'column', [], new Column('column', ['type' => Column::TYPE_MORPHS,])],
            ['nullableMorphs', 'column', [], new Column('column', ['type' => Column::TYPE_NULLABLEMORPHS,])],*/
            /* TODO Specific test
            ['rememberToken', 'column', [], new Column('column', ['type' => Column::TYPE_REMEMBERTOKEN,])],*/

        ];
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

        $this->assertEquals([$excepted], $blueprint->getColumns());
    }

}
