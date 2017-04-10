<?php

namespace Test\Repositories;

use Neutrino\Constants\Services;
use Neutrino\Model;
use Neutrino\Repositories\Repository;
use Phalcon\Db\Column;
use Phalcon\Mvc\Model\Manager as ModelManager;
use Phalcon\Mvc\Model\Message;
use Phalcon\Mvc\Model\Query as ModelQuery;
use Test\TestCase\TestCase;

class RepositoryTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage StubWrongRepository must have a $modelClass.
     */
    public function testWrongContructor()
    {
        new StubWrongRepository;
    }

    public function testContructor()
    {
        $this->assertInstanceOf(StubRepository::class, new StubRepository);
    }

    public function testCount()
    {
        $this->mockCount(1);

        $repository = new StubRepository;

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
        //$this->setValueProperty(Repository::class, 'queries', []);
        $this->mockDb(count($data), $data);

        $repository = new StubRepository;

        $result = $repository->all();

        $this->assertInstanceOf(\Phalcon\Mvc\Model\ResultsetInterface::class, $result);
        $this->assertEquals($data, $result->toArray());

        foreach ($result as $item) {
            $this->assertInstanceOf(StubModelTest::class, $item);
        }
    }

    public function testFirst()
    {
        $this->mockDb(count([['id' => 1, 'name' => 't1']]), [['id' => 1, 'name' => 't1']]);

        $repository = new StubRepository;

        $result = $repository->first();

        $this->assertInstanceOf(StubModelTest::class, $result);

        $this->assertEquals(['id' => 1, 'name' => 't1'], $result->toArray());
    }

    /**
     * @dataProvider dataAll
     */
    public function testFind($data)
    {
        $this->mockDb(count($data), $data);

        $repository = new StubRepository;

        $result = $repository->find();

        $this->assertInstanceOf(\Phalcon\Mvc\Model\ResultsetInterface::class, $result);
        $this->assertEquals($data, $result->toArray());

        foreach ($result as $key => $item) {
            $this->assertInstanceOf(StubModelTest::class, $item);
            $this->assertEquals($data[$key], $item->toArray());
        }
    }

    public function testSave()
    {
        $this->markTestIncomplete('Test to redo');
        $this->mockDb(0, null);
        $repository = new StubRepository;

        $model = new StubModelTest;
        $model->name = 'test';

        $this->assertTrue($repository->save($model));
        $this->assertTrue($repository->save([$model, $model]));
    }

    public function testSaveFailed()
    {
        $this->mockDb(0, null);
        $repository = new StubRepository;

        $model = new StubModelTest;

        $this->assertFalse($repository->save($model));
        $this->assertEquals([Message::__set_state([
            '_type'    => "PresenceOf",
            '_message' => "name is required",
            '_field'   => "name",
            '_model'   => null,
            '_code'    => 0
        ]), 'Test\Repositories\StubModelTest:save: failed. Show Test\Repositories\StubRepository::getMessages().'], $repository->getMessages());
    }

    public function testUpdate()
    {
        $this->markTestIncomplete('Test to redo');
        $this->mockDb(0, null);
        $repository = new StubRepository;

        $model = new StubModelTest;
        $model->name = 'test';

        $this->assertTrue($repository->update($model));
        $this->assertTrue($repository->update([$model, $model]));
    }

    public function testUpdateFailed()
    {
        $this->mockDb(0, null);
        $repository = new StubRepository;

        $model = new StubModelTest;
        $model->name = 'test';

        $this->assertFalse($repository->update($model));
        $this->assertEquals([Message::__set_state([
            '_type'    => 'InvalidUpdateAttempt',
            '_message' => 'Record cannot be updated because it does not exist',
            '_field'   => null,
            '_model'   => null,
            '_code'    => 0,
        ]), 'Test\Repositories\StubModelTest:update: failed. Show Test\Repositories\StubRepository::getMessages().'], $repository->getMessages());
    }

    public function testDelete()
    {
        $this->mockDb(0, null);
        $repository = new StubRepository;

        $model = new StubModelTest;

        $this->assertTrue($repository->delete($model));
        $this->assertTrue($repository->delete([$model, $model]));
    }

    public function dataCreateQuery()
    {
        return [
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias}',
                null, [], null, null, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} LIMIT :{alias}_limit_phql:',
                null, [], null, 1, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.name = :name:',
                null, ['name' => 1], null, null, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.name IN :name:',
                null, ['name' => ['abc', 'xyz']], null, null, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.name LIKE :name:',
                null, ['name' => 'test'], null, null, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.id = :id: AND {alias}.name LIKE :name: AND {alias}.status IN :status:',
                null, ['id' => 10, 'name' => 'abc', 'status' => [1, 2, 3]], null, null, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.name LIKE :name: LIMIT :{alias}_limit_phql:',
                null, ['name' => 'test'], null, 1, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.name LIKE :name: ORDER BY {alias}.name ASC LIMIT :{alias}_limit_phql:',
                null, ['name' => 'test'], ['name'], 1, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.name LIKE :name: ORDER BY {alias}.name ASC LIMIT :{alias}_limit_phql:',
                null, ['name' => 'test'], ['name' => 'ASC'], 1, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.name LIKE :name: ORDER BY {alias}.name DESC LIMIT :{alias}_limit_phql:',
                null, ['name' => 'test'], ['name' => 'DESC'], 1, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.name LIKE :name: ORDER BY {alias}.id ASC, {alias}.name DESC LIMIT :{alias}_limit_phql:',
                null, ['name' => 'test'], ['id', 'name' => 'DESC'], 1, null],
            ["SELECT * FROM " . StubModelTest::class . ' AS {alias} WHERE {alias}.name LIKE :name: ORDER BY {alias}.id ASC, {alias}.name DESC LIMIT :{alias}_limit_phql: OFFSET :{alias}_offset_phql:',
                null, ['name' => 'test'], ['id', 'name' => 'DESC'], 1, 1],
        ];
    }

    /**
     * @dataProvider dataCreateQuery
     */
    public function testCreateQuery($phql, $columns, $wheres, $orders, $limit, $offset)
    {
        $repository = new StubRepository;

        $alias = $this->getValueProperty($repository, "alias");

        $phql = str_replace('{alias}', $alias, $phql);

        //$expected = $this->getDI()->getShared('modelsManager')->createQuery($phql);

        $query = $this->invokeMethod($repository, 'createPhql', [$columns, $wheres, $orders, $limit, $offset]);
        //$queries = $this->getStaticValueProperty(Repository::class, 'queries');

        //$this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($query, '_phql'));
        $this->assertEquals($phql, $query);
        //$this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($queries[$phql], '_phql'));
    }

    public function testCreateQueryMultiple()
    {
        $repository = new StubRepository;
        //$this->setStaticValueProperty(Repository::class, 'queries', []);

        $alias = $this->getValueProperty($repository, "alias");

        $expected = $this->getDI()->getShared('modelsManager')->createQuery(
            $phql = 'SELECT * FROM ' . StubModelTest::class . " AS $alias WHERE $alias.name LIKE :name:"
        );
        $expectedSecond = $this->getDI()->getShared('modelsManager')->createQuery(
            $phqlSecond = 'SELECT * FROM ' . StubModelTest::class . " AS $alias WHERE $alias.id = :id:"
        );

        $phqlRepo = $this->invokeMethod($repository, 'createPhql', [null, ['name' => 'test']]);
        $query = $this->invokeMethod($repository, 'getQuery', [$phqlRepo]);

        $queries = $this->getValueProperty($repository, 'queries');

        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($query, '_phql'));
        $this->assertCount(1, $queries);
        $this->assertArrayHasKey($phql, $queries);
        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($queries[$phql], '_phql'));

        $phqlRepo = $this->invokeMethod($repository, 'createPhql', [null, ['name' => 'test_2']]);
        $query = $this->invokeMethod($repository, 'getQuery', [$phqlRepo]);

        $queries = $this->getValueProperty($repository, 'queries');
        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($query, '_phql'));
        $this->assertCount(1, $queries);
        $this->assertArrayHasKey($phql, $queries);
        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($queries[$phql], '_phql'));

        $phqlRepo = $this->invokeMethod($repository, 'createPhql', [null, ['id' => 2]]);
        $query = $this->invokeMethod($repository, 'getQuery', [$phqlRepo]);

        $queries = $this->getValueProperty($repository, 'queries');
        $this->assertEquals($this->getValueProperty($expectedSecond, '_phql'), $this->getValueProperty($query, '_phql'));
        $this->assertCount(2, $queries);
        $this->assertArrayHasKey($phqlSecond, $queries);
        $this->assertEquals($this->getValueProperty($expectedSecond, '_phql'), $this->getValueProperty($queries[$phqlSecond], '_phql'));
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
        $nb = floor(($end - $start) / $pad);

        $rest = ($end - $start) % $pad;

        $datas = [];
        for ($i = 0; $i < $nb; $i++) {
            $data = [];
            for ($j = 0; $j < $pad; $j++) {
                $data[] = StubModelTest::make();
            }
            $datas[] = $data;
        }

        $cdatas = count($datas);
        for ($i = 0; $i < $rest; $i++) {
            $datas[$cdatas][] = StubModelTest::make();
        }

        $query = $this->createMock(ModelQuery::class);
        $builder = $query->expects($this->exactly($e_call))
            ->method('execute');

        if (!empty($datas)) {
            $builder->willReturn(...$datas);
        }

        $modelsManager = $this->mockService(Services::MODELS_MANAGER, ModelManager::class, true);

        $modelsManager->expects($this->any())->method('createQuery')->willReturn($query);

        $repository = new StubRepository;
//        $this->setStaticValueProperty(Repository::class, 'queries', []);

        $count = 0;

        foreach ($repository->each([], $start, $end, $pad) as $item) {
            $this->assertInstanceOf(StubModelTest::class, $item);

            $count++;
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
            ->method('fetchall')
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

class StubWrongRepository extends Repository
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
     * @return \Test\Repositories\StubModelTest
     */
    public static function make($name = null)
    {
        if (is_null($name)) {
            $name = str_random();
        }

        $model = new self;

        $model->name = $name;

        return $model;
    }
}

class StubRepository extends Repository
{
    protected $modelClass = StubModelTest::class;
}
