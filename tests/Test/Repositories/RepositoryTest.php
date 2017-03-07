<?php

namespace Test\Repositories;

use Neutrino\Constants\Services;
use Neutrino\Model;
use Neutrino\Repositories\Repository;
use Phalcon\Db\Column;
use Phalcon\Mvc\Model\Message;
use Phalcon\Mvc\Model\Query;
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
        $this->setStaticValueProperty(Repository::class, 'queries', []);
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

        $model       = new StubModelTest;
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
            ["SELECT * FROM " . StubModelTest::class, [], null],
            ["SELECT * FROM " . StubModelTest::class . ' LIMIT 1', [], 1],
            ["SELECT * FROM " . StubModelTest::class . ' WHERE name = :name:', ['name' => 'test'], null],
            ["SELECT * FROM " . StubModelTest::class . ' WHERE name = :name: LIMIT 1', ['name' => 'test'], 1],
        ];
    }

    /**
     * @dataProvider dataCreateQuery
     */
    public function testCreateQuery($phql, $wheres, $limit)
    {
        $repository = new StubRepository;

        /** @var Query $expected */
        $expected = $this->getDI()->getShared('modelsManager')->createQuery($phql);

        $query = $this->invokeMethod($repository, 'createQuery', [$wheres, $limit]);
        $queries = $this->getStaticValueProperty(Repository::class, 'queries');

        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($query, '_phql'));
        $this->assertArrayHasKey($phql, $queries);
        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($queries[$phql], '_phql'));
    }

    public function testCreateQueryMultiple()
    {
        $repository = new StubRepository;
        $this->setStaticValueProperty(Repository::class, 'queries', []);

        $expected = $this->getDI()->getShared('modelsManager')->createQuery(
            $phql = 'SELECT * FROM ' . StubModelTest::class . ' WHERE name = :name:'
        );
        $expectedSecond = $this->getDI()->getShared('modelsManager')->createQuery(
            $phqlSecond = 'SELECT * FROM ' . StubModelTest::class . ' WHERE id = :id:'
        );

        $query = $this->invokeMethod($repository, 'createQuery', [['name' => 'test']]);
        $queries = $this->getStaticValueProperty(Repository::class, 'queries');

        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($query, '_phql'));
        $this->assertCount(1, $queries);
        $this->assertArrayHasKey($phql, $queries);
        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($queries[$phql], '_phql'));

        $query = $this->invokeMethod($repository, 'createQuery', [['name' => 'test_2']]);
        $queries = $this->getStaticValueProperty(Repository::class, 'queries');
        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($query, '_phql'));
        $this->assertCount(1, $queries);
        $this->assertArrayHasKey($phql, $queries);
        $this->assertEquals($this->getValueProperty($expected, '_phql'), $this->getValueProperty($queries[$phql], '_phql'));

        $query = $this->invokeMethod($repository, 'createQuery', [['id' => 'test_2']]);
        $queries = $this->getStaticValueProperty(Repository::class, 'queries');
        $this->assertEquals($this->getValueProperty($expectedSecond, '_phql'), $this->getValueProperty($query, '_phql'));
        $this->assertCount(2, $queries);
        $this->assertArrayHasKey($phqlSecond, $queries);
        $this->assertEquals($this->getValueProperty($expectedSecond, '_phql'), $this->getValueProperty($queries[$phqlSecond], '_phql'));
    }

    private function mockCount($number)
    {
        $this->mockDb(1, [['rowcount' => $number]]);
    }

    private function mockDb($numRows, $result)
    {
        $con     = $this->mockService(Services::DB, \Phalcon\Db\Adapter\Pdo\Mysql::class, true);
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
}

class StubRepository extends Repository
{
    protected $modelClass = StubModelTest::class;
}
