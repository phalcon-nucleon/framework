<?php

namespace Test\View\Volt\Filters;

use Neutrino\View\Engines\Volt\Compiler\Filters\SliceFilter;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use Test\TestCase\TestCase;

/**
 * Class SliceFilterTest
 *
 * @package Test\View\Volt\Filters
 */
class SliceFilterTest extends TestCase
{

    public function testCompileFilter()
    {
        $extension = new SliceFilter($c = new Compiler());

        $c->addFilter('slice', function ($resolvedArgs, $exprArgs) use ($extension){
            return $extension->compileFilter($resolvedArgs, $exprArgs);
        });

        $this->assertEquals('<?= array_slice([1, 2, 3], 1, 3) ?>', $c->compileString("{{ [1, 2, 3] | slice(1, 3) }}"));
    }
}
