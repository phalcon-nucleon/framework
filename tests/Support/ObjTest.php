<?php
namespace Support;

use Luxury\Support\Obj;

/**
 * Class ObjTest
 *
 * @package Support
 */
class ObjTest extends \PHPUnit_Framework_TestCase
{

    public function testValue()
    {
        $this->assertEquals('foo', Obj::value('foo'));
        $this->assertEquals('foo', Obj::value(function () {
            return 'foo';
        }));
    }

    public function testFetch()
    {
        $object = (object)['baz' => 'boo', 'foo.bar' => 123, 'null' => null];
        $this->assertEquals(123, Obj::fetch(null, null, 123));
        $this->assertEquals(null, Obj::fetch($object, null));
        $this->assertEquals('baz', Obj::fetch($object, null, 'baz'));
        $this->assertEquals('boo', Obj::fetch($object, 'baz'));
        $this->assertEquals(123, Obj::fetch($object, 'foo.bar'));
        $this->assertEquals(null, Obj::fetch($object, 'null'));
        $this->assertEquals('test', Obj::fetch($object, 'null', 'test'));
    }

    public function testRead()
    {
        $object = (object)['baz' => 'boo', 'foo.bar' => 123, 'null' => null];
        $this->assertEquals(123, Obj::read(null, null, 123));
        $this->assertEquals(null, Obj::read($object, null));
        $this->assertEquals('baz', Obj::read($object, null, 'baz'));
        $this->assertEquals('boo', Obj::read($object, 'baz'));
        $this->assertEquals(123, Obj::read($object, 'foo.bar'));
        $this->assertEquals(null, Obj::read($object, 'null'));
        $this->assertEquals(null, Obj::read($object, 'null', 'test'));
    }

    public function testSetObject()
    {
        $object = (object)['baz' => 'boo', 'foo.bar' => 123, 'null' => null];

        $this->assertEquals($object, Obj::set($object, null, null));

        $this->assertEquals(
            (object)['baz' => 123, 'foo.bar' => 123, 'null' => null],
            Obj::set($object, 'baz', 123)
        );
        $this->assertEquals(
            (object)['baz' => 123, 'foo.bar' => 123, 'null' => null],
            Obj::set($object, 'baz.foo', 123)
        );
        $this->assertEquals(
            (object)['baz' => 123, 'foo.bar' => 123, 'null' => null, 'bar' => ['foo' => 'abc']],
            Obj::set($object, 'bar.foo', 'abc')
        );
        $this->assertEquals(
            (object)['baz' => 123, 'foo.bar' => 123, 'null' => null, 'bar' => ['foo' => 'abc', 'faa' => '123']],
            Obj::set($object, 'bar.faa', '123')
        );
        $this->assertEquals(
            (object)['baz' => 123, 'foo.bar' => 123, 'null' => null, 'bar' => ['foo' => 987, 'faa' => 987]],
            Obj::set($object, 'bar.*', 987)
        );
    }

    public function testSetArray()
    {
        $array = ['baz' => 'boo', 'foo.bar' => 123, 'null' => null];

        $this->assertEquals($array, Obj::set($array, null, null));

        $this->assertEquals(
            ['baz' => 123, 'foo.bar' => 123, 'null' => null],
            Obj::set($array, 'baz', 123)
        );
        $this->assertEquals(
            ['baz' => 123, 'foo.bar' => 123, 'null' => null],
            Obj::set($array, 'baz.foo', 123)
        );
        $this->assertEquals(
            ['baz' => 123, 'foo.bar' => 123, 'null' => null, 'bar' => ['foo' => 'abc']],
            Obj::set($array, 'bar.foo', 'abc')
        );
        $this->assertEquals(
            ['baz' => 123, 'foo.bar' => 123, 'null' => null, 'bar' => ['foo' => 'abc', 'faa' => '123']],
            Obj::set($array, 'bar.faa', '123')
        );
        $this->assertEquals(
            ['baz' => 123, 'foo.bar' => 123, 'null' => null, 'bar' => ['foo' => 987, 'faa' => 987]],
            Obj::set($array, 'bar.*', 987)
        );
    }
}

