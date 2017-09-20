<?php

namespace Test\Database\Schema;

use Neutrino\Constants\Services;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapterMysql;
use Phalcon\Db\Column;
use Phalcon\Db\Dialect\Mysql as DbDialectMysql;
use Phalcon\Db\Index;
use Test\TestCase\TestCase;

class BuilderTest extends TestCase
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

        $db = $this->mockService(Services::DB, $class, true);

        $db->expects($this->once())
            ->method("getDescriptor")
            ->willReturn($description);

        if ($withDialect) {
            $dialect = $this->createMock(DbDialectMysql::class);

            $db->expects($this->once())
                ->method("getDialect")
                ->willReturn($dialect);
        }

        return $db;
    }

    public function testGetColumnListing()
    {
        $db = $this->mockDb();

        $columns = [
            new Column('id', [
                'autoIncrement' => true,
                'type'          => Column::TYPE_INTEGER,
                'unsigned'      => true,
                'notNull'       => true,
                'primary'       => true
            ])
        ];
        $db->expects($this->any())
            ->method("describeColumns")
            ->willReturn($columns);

        $builder = new Builder;

        $this->assertEquals($columns, $builder->getColumnListing('table'));
    }

    public function testHasColumn()
    {
        $db = $this->mockDb();

        $db->expects($this->any())
            ->method("describeColumns")
            ->willReturn(
                [
                    new Column('id', [
                        'autoIncrement' => true,
                        'type'          => Column::TYPE_INTEGER,
                        'unsigned'      => true,
                        'notNull'       => true,
                        'primary'       => true
                    ])
                ]
            );

        $builder = new Builder;

        $this->assertTrue($builder->hasColumn('table', 'id'));
        $this->assertTrue($builder->hasColumn('table', 'ID'));
        $this->assertFalse($builder->hasColumn('table', 'name'));
    }

    public function testHasColumns()
    {
        $db = $this->mockDb();

        $db->expects($this->any())
            ->method("describeColumns")
            ->willReturn(
                [
                    new Column('id', [
                        'autoIncrement' => true,
                        'type'          => Column::TYPE_INTEGER,
                        'unsigned'      => true,
                        'notNull'       => true,
                        'primary'       => true
                    ]),
                    new Column('name', [
                        'type'    => Column::TYPE_VARCHAR,
                        'size'    => 256,
                        'notNull' => true
                    ])
                ]
            );

        $builder = new Builder;

        $this->assertTrue($builder->hasColumns('table', ['id']));
        $this->assertTrue($builder->hasColumns('table', ['id', 'name']));
        $this->assertFalse($builder->hasColumns('table', ['id', 'name', 'other']));
    }

    public function testCreate()
    {
        $db = $this->mockDb();

        $db->expects($this->once())
            ->method("createTable")
            ->with('table', 'test', [
                'columns' => [
                    new Column('id', [
                        'autoIncrement' => true,
                        'type'          => Column::TYPE_INTEGER,
                        'unsigned'      => true,
                        'notNull'       => true,
                        'primary'       => true
                    ]),
                ]
            ]);

        (new Builder)->create('table', function ($blueprint) {
            $this->assertInstanceOf(Blueprint::class, $blueprint);

            /** @var Blueprint $blueprint */
            $blueprint->increments('id')->primary();
        });
    }

    public function testUpdate()
    {
        $db = $this->mockDb();

        $db->expects($this->once())
            ->method("describeColumns")
            ->willReturn(
                [
                    new Column('id', [
                        'autoIncrement' => true,
                        'type'          => Column::TYPE_INTEGER,
                        'unsigned'      => true,
                        'notNull'       => true,
                        'primary'       => true
                    ])
                ]
            );

        $db->expects($this->once())
            ->method("addColumn")
            ->with('table', 'test', new Column('name', [
                'type'    => Column::TYPE_VARCHAR,
                'size'    => 256,
                'notNull' => true
            ]));

        (new Builder)->table('table', function (Blueprint $blueprint) {
            $blueprint->string('name', 256);
        });
    }

    public function testUpdateWithModify()
    {
        $db = $this->mockDb();

        $db->expects($this->once())
            ->method("describeColumns")
            ->willReturn(
                [
                    new Column('id', [
                        'autoIncrement' => true,
                        'type'          => Column::TYPE_INTEGER,
                        'unsigned'      => true,
                        'notNull'       => true,
                        'primary'       => true
                    ]),
                    new Column('name', [
                        'type'    => Column::TYPE_VARCHAR,
                        'size'    => 256,
                        'notNull' => true
                    ])
                ]
            );

        $db->expects($this->once())
            ->method("modifyColumn")
            ->with(
                'table',
                'test',
                new Column('name', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 512,
                    'notNull' => false
                ]),
                new Column('name', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 256,
                    'notNull' => true
                ])
            );

        (new Builder)->table('table', function (Blueprint $blueprint) {
            $blueprint->string('name', 512)->nullable();
        });
    }

    public function testUpdateWithIndex()
    {
        $db = $this->mockDb();

        $db->expects($this->once())
            ->method("describeColumns")
            ->willReturn(
                [
                    new Column('id', [
                        'autoIncrement' => true,
                        'type'          => Column::TYPE_INTEGER,
                        'unsigned'      => true,
                        'notNull'       => true,
                        'primary'       => true
                    ])
                ]
            );

        $db->expects($this->once())
            ->method("addColumn")
            ->with('table', 'test', new Column('name', [
                'type'    => Column::TYPE_VARCHAR,
                'size'    => 256,
                'notNull' => true
            ]));

        $db->expects($this->once())
            ->method("addIndex")
            ->with('table', 'test', new Index('table_name_unique', ['name'], 'unique'));

        (new Builder)->table('table', function (Blueprint $blueprint) {
            $blueprint->string('name', 256)->unique();
        });
    }

    public function testRenameColumn()
    {
        $db = $this->mockDb();

        $db->expects($this->any())
            ->method("describeColumns")
            ->willReturn(
                [
                    new Column('id', [
                        'autoIncrement' => true,
                        'type'          => Column::TYPE_INTEGER,
                        'unsigned'      => true,
                        'notNull'       => true,
                        'primary'       => true
                    ]),
                    new Column('name', [
                        'type'    => Column::TYPE_VARCHAR,
                        'size'    => 256,
                        'notNull' => true
                    ])
                ]
            );

        $db->expects($this->once())
            ->method("modifyColumn")
            ->with(
                'table',
                'test',
                new Column('new_name', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 256,
                    'notNull' => true
                ]),
                new Column('name', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 256,
                    'notNull' => true
                ])
            );

        (new Builder)->table('table', function (Blueprint $blueprint) {
            $blueprint->renameColumn('name', 'new_name');
        });
    }

    public function testDropColumn()
    {
        $db = $this->mockDb();

        $db->expects($this->once())
            ->method("dropColumn")
            ->with('table', 'test', 'name');

        (new Builder)->table('table', function (Blueprint $blueprint) {
            $blueprint->dropColumn('name');
        });
    }

    public function testDropColumns()
    {
        $db = $this->mockDb();

        $db->expects($this->exactly(2))
            ->method("dropColumn")
            ->withConsecutive(
                ['table', 'test', 'col_1'],
                ['table', 'test', 'col_2']
            );

        (new Builder)->table('table', function (Blueprint $blueprint) {
            $blueprint->dropColumns(['col_1', 'col_2']);
        });
    }

    public function testDropTable()
    {
        $db = $this->mockDb();

        $db->expects($this->once())
            ->method("dropTable")
            ->with('table', 'test', false);

        (new Builder)->drop('table');
    }

    public function testDropTableIfExist()
    {
        $db = $this->mockDb();

        $db->expects($this->once())
            ->method("dropTable")
            ->with('table', 'test', true);

        (new Builder)->dropIfExists('table');
    }
}
