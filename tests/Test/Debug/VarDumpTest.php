<?php

namespace Test\Debug;

use Neutrino\Debug\Reflexion;
use Neutrino\Debug\VarDump;
use Test\TestCase\TestCase;

class VarDumpTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Reflexion::set(VarDump::class, 'uid', 0);
    }

    public function tearDown()
    {
        Reflexion::set(VarDump::class, 'uid', 0);

        parent::tearDown();
    }

    public function testUid()
    {
        $this->assertEquals(0, Reflexion::get(VarDump::class, 'uid'));

        $this->assertEquals(1, Reflexion::invoke(VarDump::class, 'uid'));
        $this->assertEquals(2, Reflexion::invoke(VarDump::class, 'uid'));
        $this->assertEquals(3, Reflexion::invoke(VarDump::class, 'uid'));

        $this->assertEquals(3, Reflexion::get(VarDump::class, 'uid'));
    }

    public function testCanHasChild()
    {
        $dump = $this->newVarDump();

        $this->assertEquals(false, Reflexion::invoke($dump, 'canHasChild', null));
        $this->assertEquals(false, Reflexion::invoke($dump, 'canHasChild', true));
        $this->assertEquals(false, Reflexion::invoke($dump, 'canHasChild', 'abc'));
        $this->assertEquals(false, Reflexion::invoke($dump, 'canHasChild', 123));

        $this->assertEquals(true, Reflexion::invoke($dump, 'canHasChild', []));
        $this->assertEquals(true, Reflexion::invoke($dump, 'canHasChild', (object)[]));
        $r = fopen('php://memory', 'a');
        $this->assertEquals(true, Reflexion::invoke($dump, 'canHasChild', $r));
        fclose($r);
    }

    public function testCheckArrayRecursion()
    {
        $dump = $this->newVarDump();

        $this->assertEquals(false, Reflexion::invoke($dump, 'checkArrayRecursion', []));

        $arr = [null, false, true, 123, 'abc', (object)['a'=>'a', 'b'=>'b']];
        $this->assertEquals(false, Reflexion::invoke($dump, 'checkArrayRecursion', $arr));

        $arr = [null, false, true, 123, 'abc', [null, false, true, 123, 'abc']];
        $this->assertEquals(false, Reflexion::invoke($dump, 'checkArrayRecursion', $arr));

        $arr = [null, false, true, 123, 'abc', (object)['a'=>'a', 'b'=>'b']];
        $arr[] = $arr;
        $this->assertEquals(false, Reflexion::invoke($dump, 'checkArrayRecursion', $arr));

        $arr = [null, false, true, 123, 'abc', (object)['a'=>'a', 'b'=>'b']];
        $arr[5]->arr = &$arr;
        $this->assertEquals(true, Reflexion::invoke($dump, 'checkArrayRecursion', $arr));

        $arr = [null, false, true, 123, 'abc', (object)['a'=>'a', 'b'=>'b']];
        $arr[] = &$arr;
        $this->assertEquals(true, Reflexion::invoke($dump, 'checkArrayRecursion', $arr));
    }

    public function testGenArrayHash()
    {
        $dump = $this->newVarDump();

        $arr = [null, false, true, 123, 'abc'];
        $this->assertEquals(json_encode($arr), Reflexion::invoke($dump, 'genArrayHash', $arr));

        $r = fopen('php://memory', 'a');
        $arr = [null, false, true, 123, 'abc', $r];
        $this->assertEquals(json_encode([null, false, true, 123, 'abc', intval($r) . 'stream']), Reflexion::invoke($dump, 'genArrayHash', $arr));
        fclose($r);

        $arr = [null, false, true, 123, 'abc', (object)['a'=>'a', 'b'=>'b']];
        $o = $arr[5];
        $this->assertEquals(json_encode([null, false, true, 123, 'abc', spl_object_hash($o)]), Reflexion::invoke($dump, 'genArrayHash', $arr));

        $arr = [null, false, true, 123, 'abc', (object)['a'=>'a', 'b'=>'b']];
        $arr[5]->arr = &$arr;
        $o = $arr[5];
        $this->assertEquals(json_encode([null, false, true, 123, 'abc', spl_object_hash($o)]), Reflexion::invoke($dump, 'genArrayHash', $arr));

        $arr = [null, false, true, 123, 'abc', ['a'=>'a', 'b'=>'b']];
        $this->assertEquals(json_encode([null, false, true, 123, 'abc', json_encode(['a'=>'a', 'b'=>'b'])]), Reflexion::invoke($dump, 'genArrayHash', $arr));
        $arr = [null, false, true, 123, 'abc', ['a'=>'a', 'b'=>'b']];
        $arr[5]['arr'] = &$arr;
        $this->assertEquals(json_encode([null, false, true, 123, 'abc', json_encode(['a'=>'a', 'b'=>'b', 'arr' => 'array recursion'])]), Reflexion::invoke($dump, 'genArrayHash', $arr));
    }

    public function testGetVarId()
    {
        $dump = $this->newVarDump();

        $this->assertEquals(null, Reflexion::invoke($dump, 'getVarId', null));
        $this->assertEquals(null, Reflexion::invoke($dump, 'getVarId', false));
        $this->assertEquals(null, Reflexion::invoke($dump, 'getVarId', true));
        $this->assertEquals(null, Reflexion::invoke($dump, 'getVarId', 123));
        $this->assertEquals(null, Reflexion::invoke($dump, 'getVarId', 123.456));
        $this->assertEquals(null, Reflexion::invoke($dump, 'getVarId', 'abc'));
        $this->assertEquals(null, Reflexion::invoke($dump, 'getVarId', []));
        $this->assertEquals(null, Reflexion::invoke($dump, 'getVarId', []));
        $this->assertEquals(1, Reflexion::invoke($dump, 'getVarId', $o1 = (object)[]));
        $this->assertEquals(2, Reflexion::invoke($dump, 'getVarId', $o2 = (object)[]));
        $this->assertEquals(1, Reflexion::invoke($dump, 'getVarId', $o1));
        $this->assertEquals(2, Reflexion::invoke($dump, 'getVarId', $o2));

        $arr = [(object)['a'=>'a', 'b'=>'b']];
        $arr[1]->arr = &$arr;
        $this->assertEquals(3, Reflexion::invoke($dump, 'getVarId', $arr));
        $this->assertEquals(3, Reflexion::invoke($dump, 'getVarId', $arr));

        $arr = [null, false, true, 123, 'abc', ['a'=>'a', 'b'=>'b']];
        $arr[5]['arr'] = &$arr;
        $this->assertEquals(4, Reflexion::invoke($dump, 'getVarId', $arr));
        $this->assertEquals(4, Reflexion::invoke($dump, 'getVarId', $arr));

        $r = fopen('php://memory', 'a');
        $this->assertEquals(5, Reflexion::invoke($dump, 'getVarId', $r));
        $this->assertEquals(5, Reflexion::invoke($dump, 'getVarId', $r));
        fclose($r);
    }

    public function testOutputBasic()
    {
        $this->assertNotEmpty(Reflexion::invoke(VarDump::class, 'outputBasic'));
        $this->assertEmpty(Reflexion::invoke(VarDump::class, 'outputBasic'));
    }

    public function testBasicInternalDump()
    {
        $dump = $this->newVarDump();

        $this->assertEquals('<code class="nuc-const">null</code>', Reflexion::invoke($dump, 'varDump', null));
        $this->assertEquals('<code class="nuc-const">false</code>', Reflexion::invoke($dump, 'varDump', false));
        $this->assertEquals('<code class="nuc-const">true</code>', Reflexion::invoke($dump, 'varDump', true));
        $this->assertEquals('<code class="nuc-integer">123</code>', Reflexion::invoke($dump, 'varDump', 123));
        $this->assertEquals('<code class="nuc-double">123.456</code>', Reflexion::invoke($dump, 'varDump', 123.456));

        $this->assertEquals(
            '<span class="nuc-sep">"</span><code class="nuc-string" title="3 characters">abc</code><span class="nuc-sep">"</span>',
            Reflexion::invoke($dump, 'varDump', 'abc')
        );

        $this->assertEquals(
            '<code class="nuc-array">array:0</code> <span class="nuc-closure">[</span><span class="nuc-closure nuc-close">]</span>',
            Reflexion::invoke($dump, 'varDump', [])
        );

        $this->assertEquals(
            '<code class="nuc-object" title="stdClass">stdClass</code> <span class="nuc-closure">{</span><span class="nuc-closure">}</span>',
            Reflexion::invoke($dump, 'varDump', (object)[])
        );

        if(PHP_VERSION_ID > 70200){
            $type = 'resource (closed)';
        } else {
            $type = 'unknown type';
        }

        $r = fopen('php://memory', 'a');
        fclose($r);
        $this->assertEquals('<code class="nuc-unknown">' . $type . '</code>', Reflexion::invoke($dump, 'varDump', $r));
    }

    public function testArrInternalDump()
    {
        $dump = $this->newVarDump();

        $arr = [null, 123, 'abc'];
        $arr[] = &$arr;

        $this->assertEquals(
            '<code class="nuc-array">array:4</code> <span class="nuc-closure">[</span>'.
            '<span class="nuc-toggle nuc-toggle-array" data-target="nuc-ref-1">#1</span>'.
            '<ul class="nuc-array" id="nuc-ref-1">'.
            '<li class="nuc-NULL"><code class="nuc-integer">0</code> <span class="nuc-sep">=></span> <code class="nuc-const">null</code></li>'.
            '<li class="nuc-integer"><code class="nuc-integer">1</code> <span class="nuc-sep">=></span> <code class="nuc-integer">123</code></li>'.
            '<li class="nuc-string"><code class="nuc-integer">2</code> <span class="nuc-sep">=></span> <span class="nuc-sep">"</span><code class="nuc-string" title="3 characters">abc</code><span class="nuc-sep">"</span></li>'.
            '<li class="nuc-array nuc-close"><code class="nuc-integer">3</code> <span class="nuc-sep">=></span> <code  class="nuc-array">array:4 </code> <span class="nuc-closure">[</span><span class="nuc-toggle nuc-toggle-array" data-target="nuc-ref-1">#1</span><span class="nuc-closure nuc-close">]</span></li>'.
            '</ul>'.
            '<span class="nuc-closure nuc-close">]</span>',
            Reflexion::invoke($dump, 'varDump', $arr)
        );
    }

    public function testResourceInternalDump()
    {
        $dump = $this->newVarDump();

        $r = fopen('php://memory', 'a');
        $rid = intval($r);

        $open =
            '<code class="nuc-array">array:2</code> <span class="nuc-closure">[</span>'.
            '<span class="nuc-toggle nuc-toggle-array"></span>'.
            '<ul class="nuc-array">'.
            '<li class="nuc-resource nuc-close"><code class="nuc-integer">0</code> <span class="nuc-sep">=></span> '.
            '<code class="nuc-resource">resource(@' . $rid . ' stream)</code><span class="nuc-closure">{</span>'.
            '<span class="nuc-toggle nuc-toggle-array" data-target="nuc-ref-1">@' . $rid . '</span>'.
            '<ul class="nuc-array" id="nuc-ref-1">';

        $close = '</ul>'.
            '<span class="nuc-closure nuc-close">}</span>'.
            '</li><li class="nuc-resource nuc-close"><code class="nuc-integer">1</code> <span class="nuc-sep">=></span> <code class="nuc-resource">resource(@' . $rid . ' stream)</code> <span class="nuc-closure">{</span><span class="nuc-toggle nuc-toggle-array" data-target="nuc-ref-1">@' . $rid . '</span><span class="nuc-closure nuc-close">}</span></li>'.
            '</ul>'.
            '<span class="nuc-closure nuc-close">]</span>';

        $this->assertRegExp(
            '!^' . preg_quote($open, '!') . '.+' . preg_quote($close, '!') . '$!',
            Reflexion::invoke($dump, 'varDump', [$r, $r])
        );
        fclose($r);
    }

    public function testObjInternalDump()
    {
        $dump = $this->newVarDump();

        $o = new StubDump();

        $this->assertEquals(
            '<code class="nuc-object" title="Test\Debug\StubDump">StubDump</code> <span class="nuc-closure">{</span>'.
            '<span class="nuc-toggle nuc-toggle-object" data-target="nuc-ref-1">#1</span>'.
            '<ul class="nuc-object" id="nuc-ref-1">'.
            '<li class="nuc-object nuc-close"><code class="nuc-key" title="private static self:Test\Debug\StubDump"><small class="nuc-modifier">-</small> ::self</code>: <code title="Test\Debug\StubDump" class="nuc-object">StubDump</code> '.
            '<span class="nuc-closure">{</span>'.
            '<span class="nuc-toggle nuc-toggle-object" data-target="nuc-ref-1">#1</span>'.
            '<span class="nuc-closure nuc-close">}</span></li>'.
            '<li class="nuc-NULL"><code class="nuc-key" title="private pri:NULL"><small class="nuc-modifier">-</small> pri</code>: <code class="nuc-const">null</code></li>'.
            '<li class="nuc-NULL"><code class="nuc-key" title="protected pro:NULL"><small class="nuc-modifier">#</small> pro</code>: <code class="nuc-const">null</code></li>'.
            '<li class="nuc-NULL"><code class="nuc-key" title="public pub:NULL"><small class="nuc-modifier">+</small> pub</code>: <code class="nuc-const">null</code></li>'.
            '<li class="nuc-NULL"><code class="nuc-key" title="public dyn:NULL"><small class="nuc-modifier">+</small> dyn</code>: <code class="nuc-const">null</code></li>'.
            '</ul>'.
            '<span class="nuc-closure">}</span>',
            Reflexion::invoke($dump, 'varDump', $o)
        );
    }

    public function testDump()
    {
        Reflexion::invoke(VarDump::class, 'outputBasic');

        $this->assertEquals(
            '<pre class=\'nuc-dump\' id=\'nuc-dump-1\'><code class="nuc-const">null</code></pre><script>nucDumper(\'nuc-dump-1\')</script>',
            $this->captureDump(null)
        );
        $this->assertEquals(
            '<pre class=\'nuc-dump\' id=\'nuc-dump-2\'><code class="nuc-const">true</code></pre><script>nucDumper(\'nuc-dump-2\')</script>',
            $this->captureDump(true)
        );
        $this->assertEquals(
            '<pre class=\'nuc-dump\' id=\'nuc-dump-3\'><code class="nuc-integer">123</code></pre><script>nucDumper(\'nuc-dump-3\')</script>',
            $this->captureDump(123)
        );
    }

    private function captureDump($var)
    {
        ob_start();
        VarDump::dump($var);

        return ob_get_clean();
    }

    private function newVarDump()
    {
        return Reflexion::getReflectionClass(VarDump::class)->newInstanceWithoutConstructor();
    }
}

class StubDump
{
    private static $self;

    private $pri;

    protected $pro;

    public $pub;

    public function __construct()
    {
        self::$self = $this;

        $this->dyn = null;
    }
}