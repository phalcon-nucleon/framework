<?php

namespace Test\View\Volt\Filters;

use Neutrino\View\Engines\Volt\Compiler\Filters\SplitFilter;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use Test\TestCase\TestCase;

/**
 * Class SplitFilterTest
 *
 * @package Test\View\Volt\Filters
 */
class SplitFilterTest extends TestCase
{

    public function testCompileFilter()
    {
        $extension = new SplitFilter($c = new Compiler());

        $c->addFilter('split', function ($resolvedArgs, $exprArgs) use ($extension){
            return $extension->compileFilter($resolvedArgs, $exprArgs);
        });

        $this->assertEquals("<?= str_split('str', 1) ?>", $c->compileString("{{ 'str' | split }}"));
        $this->assertEquals("<?= str_split('str', 2) ?>", $c->compileString("{{ 'str' | split('', 2) }}"));
        $this->assertEquals("<?= explode('-', 'str') ?>", $c->compileString("{{ 'str' | split('-') }}"));
        $this->assertEquals("<?= explode('-', 'str', 2) ?>", $c->compileString("{{ 'str' | split('-', 2) }}"));
    }
}
