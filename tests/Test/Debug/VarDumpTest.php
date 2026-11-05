<?php

declare(strict_types=1);

namespace Test\Debug;

use Neutrino\Debug\VarDump;
use Neutrino\Support\Reflection;
use PHPUnit\Framework\TestCase;

final class VarDumpTest extends TestCase
{
    protected function setUp(): void
    {
        Reflection::set(VarDump::class, 'uid', 0);
        Reflection::set(VarDump::class, 'assetsOutput', false);
    }

    public function testTextScalars(): void
    {
        $this->assertSame('null', VarDump::text(null));
        $this->assertSame('true', VarDump::text(true));
        $this->assertSame('123', VarDump::text(123));
        $this->assertSame('1.5', VarDump::text(1.5));
        $this->assertSame('"a \"b\"\n"', VarDump::text("a \"b\"\n"));
        $this->assertSame('Test\Debug\StubDumpSuit::Hearts = \'H\'', VarDump::text(StubDumpSuit::Hearts));
        $this->assertSame('array:0 []', VarDump::text([]));
    }

    public function testTextArray(): void
    {
        $this->assertSame(
            <<<'TXT'
            array:3 [
              0 => 1
              "key" => array:1 [
                0 => "value"
              ]
              1 => null
            ]
            TXT,
            VarDump::text([1, 'key' => ['value'], null]),
        );
    }

    public function testTextObject(): void
    {
        $object = new StubDump('read');
        $object->self = $object;

        $this->assertSame(
            <<<'TXT'
            Test\Debug\StubDump #1 {
              -::static: 3
              +self: Test\Debug\StubDump {#1}
              +public: Test\Debug\StubDumpSuit::Hearts = 'H'
              #protected: null
              -private: "private"
              -uninitialized: uninitialized
              -readonly: "read"
              +dynamic: 1
            }
            TXT,
            VarDump::text($object),
        );
    }

    public function testTextResource(): void
    {
        $resource = fopen('php://memory', 'r');

        $this->assertMatchesRegularExpression('/^resource\(@\d+ stream\) #1 \{\n  "timed_out" => false\n/', VarDump::text($resource));

        fclose($resource);

        $this->assertSame('resource (closed)', VarDump::text($resource));
    }

    public function testRecursiveArrayIsCut(): void
    {
        $array = [1];
        $array[] = &$array;

        $this->assertStringContainsString('** MAX DUMP LVL **', VarDump::text($array));
    }

    public function testHtml(): void
    {
        $this->assertSame('<code class="nuc-const">null</code>', VarDump::html(null));
        $this->assertSame('<code class="nuc-integer">123</code>', VarDump::html(123));
        $this->assertSame(
            '<span class="nuc-sep">"</span><code class="nuc-string" title="3 characters">&lt;b&gt;</code><span class="nuc-sep">"</span>',
            VarDump::html('<b>'),
        );
        $this->assertSame(
            '<code class="nuc-array">array:1</code> <span class="nuc-closure">[</span><span class="nuc-toggle nuc-toggle-array"></span>'
            . '<ul class="nuc-array"><li class="nuc-integer"><code class="nuc-integer">0</code> <span class="nuc-sep">=></span> <code class="nuc-integer">1</code></li></ul>'
            . '<span class="nuc-closure nuc-close">]</span>',
            VarDump::html([1]),
        );
    }

    public function testHtmlObject(): void
    {
        $object = new \stdClass();
        $object->self = $object;

        $this->assertSame(
            '<code class="nuc-object" title="stdClass">stdClass</code> <span class="nuc-closure">{</span>'
            . '<span class="nuc-toggle nuc-toggle-object" data-target="nuc-ref-1">#1</span><ul class="nuc-object" id="nuc-ref-1">'
            . '<li class="nuc-object nuc-close"><code class="nuc-key" title="public self"><small class="nuc-modifier">+</small> self</code>: '
            . '<code class="nuc-object" title="stdClass">stdClass</code> <span class="nuc-closure">{</span><span class="nuc-toggle nuc-toggle-object" data-target="nuc-ref-1">#1</span><span class="nuc-closure nuc-close">}</span></li>'
            . '</ul><span class="nuc-closure nuc-close">}</span>',
            VarDump::html($object),
        );
    }

    public function testDumpInTheConsole(): void
    {
        $this->expectOutputString("123\n\"a\"\n");

        VarDump::dump(123, 'a');
    }
}

enum StubDumpSuit: string
{
    case Hearts = 'H';
}

#[\AllowDynamicProperties]
final class StubDump
{
    private static int $static = 3;

    public ?object $self = null;

    public StubDumpSuit $public = StubDumpSuit::Hearts;

    protected mixed $protected = null;

    private string $private = 'private';

    private int $uninitialized;

    public function __construct(private readonly string $readonly)
    {
        $this->dynamic = 1;
    }
}
