<?php

namespace Test\View\Volt\Filters;

use Neutrino\View\Engines\Volt\Compiler\Filters\MergeFilter;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use Test\TestCase\TestCase;

/**
 * Class MergeFilterTest
 *
 * @package Test\View\Volt\Filters
 */
class MergeFilterTest extends TestCase
{
    public function testCompileFilter()
    {
        $extension = new MergeFilter($c = new Compiler());

        $c->addFilter('merge', function ($resolvedArgs, $exprArgs) use ($extension){
            return $extension->compileFilter($resolvedArgs, $exprArgs);
        });

        $this->assertEquals('<?= array_merge([1, 2, 3], [4, 5]) ?>', $c->compileString("{{ [1, 2, 3] | merge([4, 5]) }}"));
    }

}
