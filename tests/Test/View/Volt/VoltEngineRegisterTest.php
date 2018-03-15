<?php

namespace Test\View\Volt;

use Neutrino\View\Engines\Volt;
use Phalcon\Mvc\View;
use Test\TestCase\TestCase;

/**
 * Class VoltEngineRegisterTest
 *
 * @package Test\View\Volt
 */
class VoltEngineRegisterTest extends TestCase
{

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::setConfig([
          'view' => [
            'compiled_path' => __DIR__,
            'extensions' => [
              Volt\Compiler\Extensions\PhpFunctionExtension::class,
              Volt\Compiler\Extensions\StrExtension::class,
            ],
            'filters' => [
              'round' => Volt\Compiler\Filters\RoundFilter::class,
              'merge' => Volt\Compiler\Filters\MergeFilter::class,
              'slice' => Volt\Compiler\Filters\SliceFilter::class,
              'split' => Volt\Compiler\Filters\SplitFilter::class,
            ],
            'functions' => [
              'stub' => StupFunctionExtension::class
            ]
          ]
        ]);
    }

    public function testRegister()
    {
        $closure = Volt\VoltEngineRegister::getRegisterClosure();

        /** @var View\Engine\Volt $engine */
        $engine = $closure(new View(), $this->getDI());

        $this->assertEquals([
          'compiledPath' => __DIR__,
          'compiledSeparator' => '_',
          'compileAlways' => true,
        ], $engine->getOptions());

        $compiler = $engine->getCompiler();
        $this->assertCount(2, $compiler->getExtensions());
        $this->assertCount(4, $compiler->getFilters());
        $this->assertCount(2, $compiler->getFunctions());

        $this->assertEquals('<?= Neutrino\Debug\VarDump::dump(1.25) ?>', $compiler->compileString("{{ dump(1.25) }}"));
        $this->assertEquals('<?= Neutrino\Support\Str::ascii(\'abc\') ?>', $compiler->compileString("{{ str_ascii('abc') }}"));
        $this->assertEquals('<?= str_replace(\'abc\') ?>', $compiler->compileString("{{ str_replace('abc') }}"));
        $this->assertEquals('<?= array_merge([1, 2, 3], [4, 5]) ?>', $compiler->compileString("{{ [1, 2, 3] | merge([4, 5]) }}"));
        $this->assertEquals("<?= str_split('str', 1) ?>", $compiler->compileString("{{ 'str' | split }}"));
        $this->assertEquals('<?= array_slice([1, 2, 3], 1, 3) ?>', $compiler->compileString("{{ [1, 2, 3] | slice(1, 3) }}"));
        $this->assertEquals('<?= round(1.25, 0) ?>', $compiler->compileString("{{ 1.25 | round }}"));
        $this->assertEquals('<?= stub_fn_extends(1.25) ?>', $compiler->compileString("{{ stub(1.25) }}"));
    }
}

class StupFunctionExtension extends Volt\Compiler\FunctionExtend
{
    /**
     * @param string $resolvedArgs
     * @param array $exprArgs
     *
     * @return string|null
     */
    public function compileFunction($resolvedArgs, $exprArgs)
    {
        return 'stub_fn_extends(' . $resolvedArgs . ')';
    }
}
