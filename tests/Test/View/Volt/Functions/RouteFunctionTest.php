<?php

namespace Test\View\Volt\Functions;

use Neutrino\View\Engines\Volt\Compiler\Functions\RouteFunction;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use Test\TestCase\TestCase;

/**
 * Class RouteFunctionTest
 *
 * @package     Test\View\Volt\Functions
 */
class RouteFunctionTest extends TestCase
{

    public function testCompileFunction()
    {
        $extension = new RouteFunction($c = new Compiler());

        $c->addFunction('route', function ($resolvedArgs, $exprArgs) use ($extension){
            return $extension->compileFunction($resolvedArgs, $exprArgs);
        });

        $this->assertEquals(
            "<?= \$this->url->get(['for' => 'user']) ?>",
            $c->compileString("{{ route('user') }}")
        );
        $this->assertEquals(
            "<?= \$this->url->get(['for' => 'user', 'id' => 123]) ?>",
            $c->compileString("{{ route('user', ['id': 123]) }}")
        );
        $this->assertEquals(
            "<?= \$this->url->get(['for' => 'user'], ['id' => 123]) ?>",
            $c->compileString("{{ route('user', null, ['id': 123]) }}")
        );
        $this->assertEquals(
            "<?= \$this->url->get(['for' => 'user', 'id' => 123], ['q' => 'abc']) ?>",
            $c->compileString("{{ route('user', ['id': 123], ['q': 'abc']) }}")
        );
    }

}
