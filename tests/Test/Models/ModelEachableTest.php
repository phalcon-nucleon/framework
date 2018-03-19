<?php

namespace Test\Models;

use Neutrino\Model;
use Neutrino\Support\Model\Eachable;
use Test\TestCase\TestCase;

class ModelEachableTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        StubModelEachable::$searches = [];
    }

    public function testEach()
    {
        $call = 0;
        foreach (StubModelEachable::each([]) as $k => $item) {
            $this->assertEquals($call, $k);
            $this->assertEquals($call, $item);
            $call++;
        }

        $this->assertEquals(30, $call);

        $this->assertEquals([
            [
                'limit'  => 100,
                'offset' => 0,
            ],
            [
                'limit'  => 100,
                'offset' => 100,
            ],
            [
                'limit'  => 100,
                'offset' => 200,
            ],
            [
                'limit'  => 100,
                'offset' => 300,
            ]
        ], StubModelEachable::$searches);
    }

    public function testEachWithPad()
    {
        $call = 0;
        foreach (StubModelEachable::each([], 0, 100, 10) as $k => $item) {
            $this->assertEquals($call, $k);
            $this->assertEquals($call, $item);
            $call++;
        }

        $this->assertEquals(30, $call);

        $this->assertEquals([
            [
                'limit'  => 10,
                'offset' => 0,
            ],
            [
                'limit'  => 10,
                'offset' => 10,
            ],
            [
                'limit'  => 10,
                'offset' => 20,
            ],
            [
                'limit'  => 10,
                'offset' => 30,
            ]
        ], StubModelEachable::$searches);
    }

    public function testEachWithStartAfterEnd()
    {
        $call = 0;
        foreach (StubModelEachable::each([], INF, 0) as $k => $item) {
            $call++;
        }

        $this->assertEquals(0, $call);
        $this->assertEquals([], StubModelEachable::$searches);
    }
}

class StubModelEachable extends Model
{
    use Eachable;

    public static $searches = [];

    public static function find($parameters = null)
    {
        self::$searches[] = $parameters;

        $start = 10 * (count(self::$searches) - 1);

        $len = $start + 10 - 1;

        if ($start > 20) {
            return [];
        }

        return range($start, $len);
    }
}