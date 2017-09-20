<?php

namespace Test\Repositories;

use Neutrino\Constants\Services;
use Neutrino\Model;
use Neutrino\Repositories\Repository;
use Neutrino\Repositories\RepositoryModel;
use Neutrino\Repositories\RepositoryPhql;
use Neutrino\Support\Str;
use Phalcon\Db\Column;
use Phalcon\Mvc\Model\Manager as ModelManager;
use Phalcon\Mvc\Model\Message;
use Phalcon\Mvc\Model\Query as ModelQuery;
use Test\TestCase\TestCase;

class RepositoryTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage StubWrongRepositoryModel must have a $modelClass.
     */
    public function testWrongContructor()
    {
        new StubWrongRepositoryModel;
    }

    public function testContructor()
    {
        $this->assertInstanceOf(StubRepositoryModel::class, new StubRepositoryModel);
    }

    public function testCount()
    {
        $this->mockCount(1);

        $repository = new StubRepositoryModel;

        $this->assertEquals(1, $repository->count());
    }

    public function dataAll()
    {
        return [
            [[]],
            [[['id' => 1, 'name' => 't1'], ['id' => 2, 'name' => 't2'], ['id' => 3, 'name' => 't3']]],
        ];
    }

    /**
     * @dataProvider dataAll
     */
    public function testAll($data)
    {
        $debug = new \Phalcon\Debug\Dump();

        xdebug_start_function_monitor([\Phalcon\Mvc\Model\Resultset\Simple::class.'->toArray']);
        try {
            //$this->setValueProperty(Repository::class, 'queries', []);
            $this->mockDb(count($data), $data);

            $repository = new StubRepositoryModel;

            $result = $repository->all();

            $this->assertInstanceOf(\Phalcon\Mvc\Model\ResultsetInterface::class, $result);

            foreach ($result as $item) {
                $this->assertInstanceOf(StubModelTest::class, $item);
            }

            //$this->assertEquals($data, $result->toArray());
        } catch (\Exception $e) {
            echo PHP_EOL;
            echo $e->getMessage();
            echo PHP_EOL;
            echo $e->getTraceAsString();
            echo PHP_EOL;
            if(isset($result)){
                $debug->one($result);
                var_dump($result);
                xdebug_debug_zval('result');
                var_dump(xdebug_get_monitored_functions());
            }
            else
                echo 'no result';

            throw $e;
        } finally{
            xdebug_stop_function_monitor();
        }
    }

    public function testFirst()
    {
        $data = [['id' => 1, 'name' => 't1']];
        $this->mockDb(count($data), $data);

        $repository = new StubRepositoryModel;

        $result = $repository->first();

        $this->assertInstanceOf(StubModelTest::class, $result);

        $this->assertEquals(['id' => 1, 'name' => 't1'], $result->toArray());
    }

    public function testFirstOrNewFound()
    {
        $data = [['id' => 1, 'name' => 't1']];
        $this->mockDb(count($data), $data);

        $repository = new StubRepositoryModel;

        $result = $repository->firstOrNew();

        $this->assertInstanceOf(StubModelTest::class, $result);

        $this->assertEquals(['id' => 1, 'name' => 't1'], $result->toArray());
    }

    public function testFirstOrNewNotFound()
    {
        $this->mockDb(0, []);

        $repository = new StubRepositoryModel;

        $result = $repository->firstOrNew(['name' => 'name']);

        $this->assertInstanceOf(StubModelTest::class, $result);

        $this->assertEquals(['id' => null, 'name' => 'name'], $result->toArray());
    }

    /**
     * @dataProvider dataAll
     */
    public function testFind($data)
    {
        $debug = new \Phalcon\Debug\Dump();

        xdebug_start_function_monitor([\Phalcon\Mvc\Model\Resultset\Simple::class.'->toArray']);
        try {
            $this->mockDb(count($data), $data);

            $repository = new StubRepositoryModel;

            $result = $repository->find();

            $this->assertInstanceOf(\Phalcon\Mvc\Model\ResultsetInterface::class, $result);
            foreach ($result as $key => $item) {
                $this->assertInstanceOf(StubModelTest::class, $item);
                $this->assertEquals($data[$key], $item->toArray());
            }
            //$this->assertEquals($data, $result->toArray());
        } catch (\Exception $e) {
            echo PHP_EOL;
            echo $e->getMessage();
            echo PHP_EOL;
            echo $e->getTraceAsString();
            echo PHP_EOL;
            if(isset($result)){
                $debug->one($result);
                var_dump($result);
                xdebug_debug_zval('result');
                var_dump(xdebug_get_monitored_functions());
            }
            else
                echo 'no result';

            throw $e;
        } finally{
            xdebug_stop_function_monitor();
        }
    }

    public function testSave()
    {
        $this->markTestIncomplete('Test to redo');
        $this->mockDb(0, null);
        $repository = new StubRepositoryModel;

        $model = new StubModelTest;
        $model->name = 'test';

        $this->assertTrue($repository->save($model));
        $this->assertTrue($repository->save([$model, $model]));
    }

    public function testSaveFailed()
    {
        $this->mockDb(0, null);
        $repository = new StubRepositoryModel;

        $model = new StubModelTest;

        $this->assertFalse($repository->save($model));
        $this->assertEquals([Message::__set_state([
            '_type'    => "PresenceOf",
            '_message' => "name is required",
            '_field'   => "name",
            '_model'   => null,
            '_code'    => 0
        ]), 'Test\Repositories\StubModelTest:save: failed. Show ' . StubRepositoryModel::class . '::getMessages().'], $repository->getMessages());
    }

    public function testSaveFailedWithoutTransaction()
    {
        $this->mockDb(0, null);
        $repository = new StubRepositoryModel;

        $model = new StubModelTest;

        $this->assertFalse($repository->save($model, false));
        $this->assertEquals([Message::__set_state([
            '_type'    => "PresenceOf",
            '_message' => "name is required",
            '_field'   => "name",
            '_model'   => null,
            '_code'    => 0
        ]), 'Test\Repositories\StubModelTest:save: failed. Show ' . StubRepositoryModel::class . '::getMessages().'], $repository->getMessages());
    }

    public function testUpdate()
    {
        $this->markTestIncomplete('Test to redo');
        $this->mockDb(0, null);
        $repository = new StubRepositoryModel;

        $model = new StubModelTest;
        $model->name = 'test';

        $this->assertTrue($repository->update($model));
        $this->assertTrue($repository->update([$model, $model]));
    }

    public function testUpdateFailed()
    {
        $this->mockDb(0, null);
        $repository = new StubRepositoryModel;

        $model = new StubModelTest;
        $model->name = 'test';

        $this->assertFalse($repository->update($model));
        $this->assertEquals([Message::__set_state([
            '_type'    => 'InvalidUpdateAttempt',
            '_message' => 'Record cannot be updated because it does not exist',
            '_field'   => null,
            '_model'   => null,
            '_code'    => 0,
        ]), 'Test\Repositories\StubModelTest:update: failed. Show ' . StubRepositoryModel::class . '::getMessages().'], $repository->getMessages());
    }

    public function testUpdateFailedWithoutTransaction()
    {
        $this->mockDb(0, null);
        $repository = new StubRepositoryModel;

        $model = new StubModelTest;
        $model->name = 'test';

        $this->assertFalse($repository->update($model, false));
        $this->assertEquals([Message::__set_state([
            '_type'    => 'InvalidUpdateAttempt',
            '_message' => 'Record cannot be updated because it does not exist',
            '_field'   => null,
            '_model'   => null,
            '_code'    => 0,
        ]), 'Test\Repositories\StubModelTest:update: failed. Show ' . StubRepositoryModel::class . '::getMessages().'], $repository->getMessages());
    }

    public function testDelete()
    {
        $this->mockDb(0, null);
        $repository = new StubRepositoryModel;

        $model = new StubModelTest;

        $this->assertTrue($repository->delete($model));
        $this->assertTrue($repository->delete([$model, $model]));
    }

    public function dataEach()
    {
        return [
            [0, 20, 5, 20, 4],
            [5, 20, 5, 15, 3],
            [0, 33, 5, 33, 7],
            [15, 33, 5, 18, 4],
            [0, 20, 20, 20, 1],
            [0, 20, 100, 20, 1],
            [0, 0, 100, 0, 0],
            [20, 0, 100, 0, 0],
        ];
    }

    /**
     * @dataProvider dataEach
     */
    public function testEach($start, $end, $pad, $e_count, $e_call)
    {
        $d = [];
        $nb = floor(($end - $start) / $pad);

        $rest = ($end - $start) % $pad;

        $datas = [];
        for ($i = 0; $i < $nb; $i++) {
            $data = [];
            for ($j = 0; $j < $pad; $j++) {
                $data[] = $d[] = StubModelTest::make(null, true);
            }
            $datas[] = $data;
        }


        $cdatas = count($datas);
        for ($i = 0; $i < $rest; $i++) {
            $datas[$cdatas][] = $d[] = StubModelTest::make(null, true);
        }

        $repository = new StubRepositoryModel;

        $count = 0;
        $page = 0;
        if (isset($datas[0])) {
            $this->mockDb(count($datas[0]), $datas[0]);
        }

        foreach ($repository->each([], $start, $end, $pad) as $item) {
            $this->assertInstanceOf(StubModelTest::class, $item);

            $count++;
            if ($count % $pad === 0 && $count !== $e_count) {
                $page++;
                $this->mockDb(count($datas[$page]), $datas[$page]);
            }
        }

        $this->assertEquals($e_count, $count);
    }

    private function mockCount($number)
    {
        $this->mockDb(1, [['rowcount' => $number]]);
    }

    private function mockDb($numRows, $result)
    {
        $con = $this->mockService(Services::DB, \Phalcon\Db\Adapter\Pdo\Mysql::class, true);
        $dialect = $this->createMock(\Phalcon\Db\Dialect\Mysql::class);
        $results = $this->createMock(\Phalcon\Db\Result\Pdo::class);

        $results->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue($numRows));

        $results->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue($result));

        $dialect->expects($this->any())
            ->method('select')
            ->will($this->returnValue(null));

        $con->expects($this->any())
            ->method('getDialect')
            ->will($this->returnValue($dialect));

        $con->expects($this->any())
            ->method('query')
            ->will($this->returnValue($results));

        $con->expects($this->any())
            ->method('execute');

        $con->expects($this->any())
            ->method('tableExists')
            ->will($this->returnValue(true));
    }
}

class StubWrongRepositoryModel extends Repository
{
    protected $modelClass = '';
}

class StubModelTest extends Model
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    public function initialize()
    {
        parent::initialize();

        $this->setSource("test");

        $this->primary('id', Column::TYPE_INTEGER);

        $this->column('name', Column::TYPE_VARCHAR);
    }

    /**
     * @param null $name
     *
     * @return \Test\Repositories\StubModelTest|array
     */
    public static function make($name = null, $asArray = false)
    {
        if (is_null($name)) {
            $name = Str::random();
        }
        if ($asArray) {
            return [
                'name' => $name
            ];
        }
        $model = new self;

        $model->name = $name;

        return $model;
    }
}

class StubRepositoryModel extends Repository
{
    protected $modelClass = StubModelTest::class;
}
