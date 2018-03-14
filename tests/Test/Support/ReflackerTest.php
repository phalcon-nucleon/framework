<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 31/07/2017
 * Time: 11:02
 */

namespace Test\Support;

use Neutrino\Debug\Reflexion;

class ReflackerTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $stb = new StubHacker();

        $this->assertEquals(123, Reflexion::get($stb, 'privateProp'));
        $this->assertEquals(456, Reflexion::get($stb, 'protectedProp'));
        $this->assertEquals(789, Reflexion::get($stb, 'publicProp'));

        $this->assertEquals('abc', Reflexion::get($stb, 'privateStaticProp'));
        $this->assertEquals('def', Reflexion::get($stb, 'protectedStaticProp'));
        $this->assertEquals('ghi', Reflexion::get($stb, 'publicStaticProp'));

        $this->assertEquals('abc', Reflexion::get(StubHacker::class, 'privateStaticProp'));
        $this->assertEquals('def', Reflexion::get(StubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('ghi', Reflexion::get(StubHacker::class, 'publicStaticProp'));

        // ---- TEST EXTENDS
        $estb = new ExtendsStubHacker();

        $this->assertEquals(123, Reflexion::get($estb, 'privateProp'));
        $this->assertEquals(456, Reflexion::get($estb, 'protectedProp'));
        $this->assertEquals(789, Reflexion::get($estb, 'publicProp'));
        $this->assertEquals(1234, Reflexion::get($estb, 'privateProp2'));
        $this->assertEquals(4567, Reflexion::get($estb, 'protectedProp2'));

        $this->assertEquals('abc', Reflexion::get($estb, 'privateStaticProp'));
        $this->assertEquals('def', Reflexion::get($estb, 'protectedStaticProp'));
        $this->assertEquals('opq', Reflexion::get($estb, 'publicStaticProp'));
        $this->assertEquals('opq', Reflexion::get($stb, 'publicStaticProp'));

        $this->assertEquals('abc', Reflexion::get(ExtendsStubHacker::class, 'privateStaticProp'));
        $this->assertEquals('def', Reflexion::get(ExtendsStubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('opq', Reflexion::get(ExtendsStubHacker::class, 'publicStaticProp'));
        $this->assertEquals('opq', Reflexion::get(StubHacker::class, 'publicStaticProp'));
    }

    public function testSet()
    {
        $stb = new StubHacker();

        Reflexion::set($stb, 'privateProp', 111);
        Reflexion::set($stb, 'protectedProp', 444);
        Reflexion::set($stb, 'publicProp', 777);

        $this->assertEquals(111, Reflexion::get($stb, 'privateProp'));
        $this->assertEquals(444, Reflexion::get($stb, 'protectedProp'));
        $this->assertEquals(777, Reflexion::get($stb, 'publicProp'));

        Reflexion::set($stb, 'privateStaticProp', 'aaa');
        Reflexion::set($stb, 'protectedStaticProp', 'ddd');
        Reflexion::set($stb, 'publicStaticProp', 'ggg');

        $this->assertEquals('aaa', Reflexion::get($stb, 'privateStaticProp'));
        $this->assertEquals('ddd', Reflexion::get($stb, 'protectedStaticProp'));
        $this->assertEquals('ggg', Reflexion::get($stb, 'publicStaticProp'));
        $this->assertEquals('aaa', Reflexion::get(StubHacker::class, 'privateStaticProp'));
        $this->assertEquals('ddd', Reflexion::get(StubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('ggg', Reflexion::get(StubHacker::class, 'publicStaticProp'));

        Reflexion::set(StubHacker::class, 'privateStaticProp', 'bbb');
        Reflexion::set(StubHacker::class, 'protectedStaticProp', 'fff');
        Reflexion::set(StubHacker::class, 'publicStaticProp', 'hhh');

        $this->assertEquals('bbb', Reflexion::get($stb, 'privateStaticProp'));
        $this->assertEquals('fff', Reflexion::get($stb, 'protectedStaticProp'));
        $this->assertEquals('hhh', Reflexion::get($stb, 'publicStaticProp'));
        $this->assertEquals('bbb', Reflexion::get(StubHacker::class, 'privateStaticProp'));
        $this->assertEquals('fff', Reflexion::get(StubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('hhh', Reflexion::get(StubHacker::class, 'publicStaticProp'));

        // ---- TEST EXTENDS
        $estb = new ExtendsStubHacker();

        Reflexion::set($estb, 'privateProp', 111);
        Reflexion::set($estb, 'protectedProp', 444);
        Reflexion::set($estb, 'publicProp', 777);
        Reflexion::set($estb, 'privateProp2', 1111);
        Reflexion::set($estb, 'protectedProp2', 4444);

        $this->assertEquals(111, Reflexion::get($estb, 'privateProp'));
        $this->assertEquals(444, Reflexion::get($estb, 'protectedProp'));
        $this->assertEquals(777, Reflexion::get($estb, 'publicProp'));
        $this->assertEquals(1111, Reflexion::get($estb, 'privateProp2'));
        $this->assertEquals(4444, Reflexion::get($estb, 'protectedProp2'));

        Reflexion::set($estb, 'privateStaticProp', 'aaaa');
        Reflexion::set($estb, 'protectedStaticProp', 'dddd');
        Reflexion::set($estb, 'publicStaticProp', 'gggg');

        $this->assertEquals('aaaa', Reflexion::get($stb, 'privateStaticProp'));
        $this->assertEquals('dddd', Reflexion::get($stb, 'protectedStaticProp'));
        $this->assertEquals('gggg', Reflexion::get($stb, 'publicStaticProp'));
        $this->assertEquals('aaaa', Reflexion::get(StubHacker::class, 'privateStaticProp'));
        $this->assertEquals('dddd', Reflexion::get(StubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('gggg', Reflexion::get(StubHacker::class, 'publicStaticProp'));
        $this->assertEquals('aaaa', Reflexion::get($estb, 'privateStaticProp'));
        $this->assertEquals('dddd', Reflexion::get($estb, 'protectedStaticProp'));
        $this->assertEquals('gggg', Reflexion::get($estb, 'publicStaticProp'));
        $this->assertEquals('aaaa', Reflexion::get(ExtendsStubHacker::class, 'privateStaticProp'));
        $this->assertEquals('dddd', Reflexion::get(ExtendsStubHacker::class, 'protectedStaticProp'));
        $this->assertEquals('gggg', Reflexion::get(ExtendsStubHacker::class, 'publicStaticProp'));
    }

    public function testInvoke()
    {
        $stb = new StubHacker();

        $this->assertEquals(StubHacker::class . '::privateMethod', Reflexion::invoke($stb, 'privateMethod'));
        $this->assertEquals(StubHacker::class . '::protectedMethod', Reflexion::invoke($stb, 'protectedMethod'));
        $this->assertEquals(StubHacker::class . '::publicMethod', Reflexion::invoke($stb, 'publicMethod'));

        $this->assertEquals(StubHacker::class . '::privateStaticMethod', Reflexion::invoke($stb, 'privateStaticMethod'));
        $this->assertEquals(StubHacker::class . '::protectedStaticMethod', Reflexion::invoke($stb, 'protectedStaticMethod'));
        $this->assertEquals(StubHacker::class . '::publicStaticMethod', Reflexion::invoke($stb, 'publicStaticMethod'));

        $this->assertEquals(StubHacker::class . '::privateStaticMethod', Reflexion::invoke(StubHacker::class, 'privateStaticMethod'));
        $this->assertEquals(StubHacker::class . '::protectedStaticMethod', Reflexion::invoke(StubHacker::class, 'protectedStaticMethod'));
        $this->assertEquals(StubHacker::class . '::publicStaticMethod', Reflexion::invoke(StubHacker::class, 'publicStaticMethod'));


        // ---- TEST EXTENDS
        $estb = new ExtendsStubHacker();
        $this->assertEquals(StubHacker::class . '::privateStaticMethod', Reflexion::invoke(ExtendsStubHacker::class, 'privateStaticMethod'));
        $this->assertEquals(StubHacker::class . '::protectedStaticMethod', Reflexion::invoke(ExtendsStubHacker::class, 'protectedStaticMethod'));

        $this->assertEquals(StubHacker::class . '::privateStaticMethod', Reflexion::invoke($estb, 'privateStaticMethod'));
        $this->assertEquals(StubHacker::class . '::protectedStaticMethod', Reflexion::invoke($estb, 'protectedStaticMethod'));

    }

    /**
     * @expectedException \ReflectionException
     */
    public function testFail()
    {
        $stb = new StubHacker();

        Reflexion::get($stb, 'something nonexistent');
    }
}

class StubHacker
{
    public $publicProp;

    protected $protectedProp;

    private $privateProp;

    public static $publicStaticProp;

    protected static $protectedStaticProp;

    private static $privateStaticProp;

    public function __construct()
    {
        self::$privateStaticProp = 'abc';
        self::$protectedStaticProp = 'def';
        self::$publicStaticProp = 'ghi';
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

        self::$publicStaticProp = 'opq';

        $this->privateProp2 = 1234;
        $this->protectedProp2 = 4567;
    }

    private function privateMethod2()
    {

    }
}