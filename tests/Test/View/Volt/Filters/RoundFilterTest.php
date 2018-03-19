<?php

namespace Test\View\Volt\Filters;

use Neutrino\View\Engines\Volt\Compiler\Filters\RoundFilter;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use Test\TestCase\TestCase;

/**
 * Class RoundFilterTest
 *
 * @package Test\View\Volt\Filters
 */
class RoundFilterTest extends TestCase
{

    public function testCompileFilter()
    {
        $extension = new RoundFilter($c = new Compiler());

        $c->addFilter('round', function ($resolvedArgs, $exprArgs) use ($extension){
            return $extension->compileFilter($resolvedArgs, $exprArgs);
        });

        $this->assertEquals('<?= round(1.25, 0) ?>', $c->compileString("{{ 1.25 | round }}"));
        $this->assertEquals('<?= round(1.25, 2) ?>', $c->compileString("{{ 1.25 | round(2) }}"));
        $this->assertEquals('<?= ceil(1.25) ?>', $c->compileString("{{ 1.25 | round('ceil') }}"));
        $this->assertEquals('<?= ceil(1.25*(10**2))/(10**2) ?>', $c->compileString("{{ 1.25 | round(2, 'ceil') }}"));
        $this->assertEquals('<?= floor(1.25) ?>', $c->compileString("{{ 1.25 | round('floor') }}"));
        $this->assertEquals('<?= floor(1.25*(10**2))/(10**2) ?>', $c->compileString("{{ 1.25 | round(2, 'floor') }}"));
    }

}
