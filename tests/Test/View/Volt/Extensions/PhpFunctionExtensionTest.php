<?php

namespace Test\View\Volt\Extensions;

use Neutrino\View\Engines\Volt\Compiler\Extensions\PhpFunctionExtension;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use Test\TestCase\TestCase;

/**
 * Class PhpFunctionExtensionTest
 *
 * @package Test\View\Volt\Extensions
 */
class PhpFunctionExtensionTest extends TestCase
{

    public function testCompileFunction()
    {
        $extension = new PhpFunctionExtension(new Compiler());

        $defs = get_defined_functions();

        foreach ($defs['internal'] as $function) {
            $this->assertEquals(
                $function . "(...arguments...)",
                $extension->compileFunction($function, "...arguments...", null)
            );
        }
        $this->assertEquals(null, $extension->compileFunction('no_exist_function', "", null));
    }

    public function testCompileFilter()
    {
        $extension = new PhpFunctionExtension(new Compiler());

        $this->assertEquals(null, $extension->compileFilter(null, null, null));
    }

    public function testResolveExpression()
    {
        $extension = new PhpFunctionExtension(new Compiler());

        $this->assertEquals(null, $extension->resolveExpression(null));
    }

    public function testCompileStatement()
    {
        $extension = new PhpFunctionExtension(new Compiler());

        $this->assertEquals(null, $extension->compileStatement(null));
    }
}
