<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 31/07/2017
 * Time: 11:02
 */

namespace Test\Support;

use Neutrino\Support\Reflacker;

class ReflackerTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $stb = new StubHacker();

        $this->assertEquals(123, Reflacker::get($stb, 'privateProp'));
        $this->assertEquals(456, Reflacker::get($stb, 'protectedProp'));
        $this->assertEquals(789, Reflacker::get($stb, 'publicProp'));

        $this->assertEquals('abc', Reflacker::get($stb, 'privateStaticProp'));
        $this->assertEquals('def', Reflacker::get($stb, 'protectedStaticProp'));
        $this->assertEquals('ghi', Reflacker::get($stb, 'publicStaticProp'));

        $this->assertEquals('abc', Reflacker::get(StubHacker::class, 'privateStaticProp'));
        $this->assertEquals('def', Reflacker::get(StubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('ghi', Reflacker::get(StubHacker::class, 'publicStaticProp'));

        // ---- TEST EXTENDS
        $estb = new ExtendsStubHacker();

        $this->assertEquals(123, Reflacker::get($estb, 'privateProp'));
        $this->assertEquals(456, Reflacker::get($estb, 'protectedProp'));
        $this->assertEquals(789, Reflacker::get($estb, 'publicProp'));
        $this->assertEquals(1234, Reflacker::get($estb, 'privateProp2'));
        $this->assertEquals(4567, Reflacker::get($estb, 'protectedProp2'));

        $this->assertEquals('abc', Reflacker::get($estb, 'privateStaticProp'));
        $this->assertEquals('def', Reflacker::get($estb, 'protectedStaticProp'));
        $this->assertEquals('ghi', Reflacker::get($estb, 'publicStaticProp'));

        $this->assertEquals('abc', Reflacker::get(ExtendsStubHacker::class, 'privateStaticProp'));
        $this->assertEquals('def', Reflacker::get(ExtendsStubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('ghi', Reflacker::get(ExtendsStubHacker::class, 'publicStaticProp'));
    }

    public function testSet()
    {
        $stb = new StubHacker();

        Reflacker::set($stb, 'privateProp', 111);
        Reflacker::set($stb, 'protectedProp', 444);
        Reflacker::set($stb, 'publicProp', 777);

        $this->assertEquals(111, Reflacker::get($stb, 'privateProp'));
        $this->assertEquals(444, Reflacker::get($stb, 'protectedProp'));
        $this->assertEquals(777, Reflacker::get($stb, 'publicProp'));

        Reflacker::set($stb, 'privateStaticProp', 'aaa');
        Reflacker::set($stb, 'protectedStaticProp', 'ddd');
        Reflacker::set($stb, 'publicStaticProp', 'ggg');

        $this->assertEquals('aaa', Reflacker::get($stb, 'privateStaticProp'));
        $this->assertEquals('ddd', Reflacker::get($stb, 'protectedStaticProp'));
        $this->assertEquals('ggg', Reflacker::get($stb, 'publicStaticProp'));
        $this->assertEquals('aaa', Reflacker::get(StubHacker::class, 'privateStaticProp'));
        $this->assertEquals('ddd', Reflacker::get(StubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('ggg', Reflacker::get(StubHacker::class, 'publicStaticProp'));

        Reflacker::set(StubHacker::class, 'privateStaticProp', 'bbb');
        Reflacker::set(StubHacker::class, 'protectedStaticProp', 'fff');
        Reflacker::set(StubHacker::class, 'publicStaticProp', 'hhh');

        $this->assertEquals('bbb', Reflacker::get($stb, 'privateStaticProp'));
        $this->assertEquals('fff', Reflacker::get($stb, 'protectedStaticProp'));
        $this->assertEquals('hhh', Reflacker::get($stb, 'publicStaticProp'));
        $this->assertEquals('bbb', Reflacker::get(StubHacker::class, 'privateStaticProp'));
        $this->assertEquals('fff', Reflacker::get(StubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('hhh', Reflacker::get(StubHacker::class, 'publicStaticProp'));

        // ---- TEST EXTENDS
        $estb = new ExtendsStubHacker();

        Reflacker::set($estb, 'privateProp', 111);
        Reflacker::set($estb, 'protectedProp', 444);
        Reflacker::set($estb, 'publicProp', 777);
        Reflacker::set($estb, 'privateProp2', 1111);
        Reflacker::set($estb, 'protectedProp2', 4444);

        $this->assertEquals(111, Reflacker::get($estb, 'privateProp'));
        $this->assertEquals(444, Reflacker::get($estb, 'protectedProp'));
        $this->assertEquals(777, Reflacker::get($estb, 'publicProp'));
        $this->assertEquals(1111, Reflacker::get($estb, 'privateProp2'));
        $this->assertEquals(4444, Reflacker::get($estb, 'protectedProp2'));

        Reflacker::set($estb, 'privateStaticProp', 'aaaa');
        Reflacker::set($estb, 'protectedStaticProp', 'dddd');
        Reflacker::set($estb, 'publicStaticProp', 'gggg');

        $this->assertEquals('aaaa', Reflacker::get($stb, 'privateStaticProp'));
        $this->assertEquals('dddd', Reflacker::get($stb, 'protectedStaticProp'));
        $this->assertEquals('gggg', Reflacker::get($stb, 'publicStaticProp'));
        $this->assertEquals('aaaa', Reflacker::get(StubHacker::class, 'privateStaticProp'));
        $this->assertEquals('dddd', Reflacker::get(StubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('gggg', Reflacker::get(StubHacker::class, 'publicStaticProp'));
        $this->assertEquals('aaaa', Reflacker::get($estb, 'privateStaticProp'));
        $this->assertEquals('dddd', Reflacker::get($estb, 'protectedStaticProp'));
        $this->assertEquals('gggg', Reflacker::get($estb, 'publicStaticProp'));
        $this->assertEquals('aaaa', Reflacker::get(ExtendsStubHacker::class, 'privateStaticProp'));
        $this->assertEquals('dddd', Reflacker::get(ExtendsStubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('gggg', Reflacker::get(ExtendsStubHacker::class, 'publicStaticProp'));
    }

    public function testInvoke()
    {
        $stb = new StubHacker();

        $this->assertEquals(StubHacker::class . '::privateMethod', Reflacker::invoke($stb, 'privateMethod'));
        $this->assertEquals(StubHacker::class . '::protectedMethod', Reflacker::invoke($stb, 'protectedMethod'));
        $this->assertEquals(StubHacker::class . '::publicMethod', Reflacker::invoke($stb, 'publicMethod'));

        $this->assertEquals(StubHacker::class . '::privateStaticMethod', Reflacker::invoke($stb, 'privateStaticMethod'));
        $this->assertEquals(StubHacker::class . '::protectedStaticMethod', Reflacker::invoke($stb, 'protectedStaticMethod'));
        $this->assertEquals(StubHacker::class . '::publicStaticMethod', Reflacker::invoke($stb, 'publicStaticMethod'));

        $this->assertEquals(StubHacker::class . '::privateStaticMethod', Reflacker::invoke(StubHacker::class, 'privateStaticMethod'));
        $this->assertEquals(StubHacker::class . '::protectedStaticMethod', Reflacker::invoke(StubHacker::class, 'protectedStaticMethod'));
        $this->assertEquals(StubHacker::class . '::publicStaticMethod', Reflacker::invoke(StubHacker::class, 'publicStaticMethod'));


        // ---- TEST EXTENDS
        $estb = new ExtendsStubHacker();
        $this->assertEquals(StubHacker::class . '::privateStaticMethod', Reflacker::invoke(ExtendsStubHacker::class, 'privateStaticMethod'));
        $this->assertEquals(StubHacker::class . '::protectedStaticMethod', Reflacker::invoke(ExtendsStubHacker::class, 'protectedStaticMethod'));

        $this->assertEquals(StubHacker::class . '::privateStaticMethod', Reflacker::invoke($estb, 'privateStaticMethod'));
        $this->assertEquals(StubHacker::class . '::protectedStaticMethod', Reflacker::invoke($estb, 'protectedStaticMethod'));

    }

    /**
     * @expectedException \ReflectionException
     */
    public function testFail()
    {
        $stb = new StubHacker();

        Reflacker::get($stb, 'something nonexistent');
    }
}

class StubHacker
{
    public $publicProp;

    protected $protectedProp;

    private $privateProp;

    public static $publicStaticProp = 'ghi';

    protected static $protectedStaticProp = 'def';

    private static $privateStaticProp = 'abc';

    public function __construct()
    {
        $this->privateProp = 123;
        $this->protectedProp = 456;
        $this->publicProp = 789;
    }

    public function publicMethod()
    {
        return __METHOD__;
    }

    protected function protectedMethod()
    {
        return __METHOD__;
    }

    private function privateMethod()
    {
        return __METHOD__;
    }

    public static function publicStaticMethod()
    {
        return __METHOD__;
    }

    protected static function protectedStaticMethod()
    {
        return __METHOD__;
    }

    private static function privateStaticMethod()
    {
        return __METHOD__;
    }
}

class ExtendsStubHacker extends StubHacker
{
    protected $protectedProp2;

    private $privateProp2;

    public function __construct()
    {
        parent::__construct();

        $this->privateProp2 = 1234;
        $this->protectedProp2 = 4567;
    }

    private function privateMethod2()
    {

    }
}