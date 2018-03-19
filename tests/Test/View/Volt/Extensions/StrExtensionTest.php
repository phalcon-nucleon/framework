<?php

namespace Test\View\Volt\Extensions;

use Neutrino\Debug\Reflexion;
use Neutrino\Support\Str;
use Neutrino\View\Engines\Volt\Compiler\Extensions\StrExtension;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use Test\TestCase\TestCase;

/**
 * Class StrExtensionTest
 *
 * @package Test\View\Volt\Extensions
 */
class StrExtensionTest extends TestCase
{

    public function testCompileFunction()
    {
        $extension = new StrExtension(new Compiler());

        $methods = Reflexion::getReflectionMethods(Str::class);

        foreach ($methods as $method) {
            if ($method->isPublic()) {
                $this->assertEquals(
                    Str::class . '::' . $method->getName() . '(...arguments...)',
                    $extension->compileFunction('str_' . $method->getName(), '...arguments...', null)
                );
            } else {
                $this->assertEquals(
                    null,
                    $extension->compileFunction('str_' . $method->getName(), '...arguments...', null)
                );
            }
        }

        $this->assertEquals(
            null,
            $extension->compileFunction('str_replace', '...arguments...', null)
        );
    }

    public function testCompileFilter()
    {
        $extension = new StrExtension(new Compiler());

        foreach (['limit', 'words', 'slug'] as $method) {
            $this->assertEquals(
                Str::class . '::' . $method . '(...arguments...)',
                $extension->compileFilter($method, '...arguments...', null)
            );
        }

        $this->assertEquals(
            null,
            $extension->compileFilter('ascii', '...arguments...', null)
        );
    }

    public function testResolveExpression()
    {
        $extension = new StrExtension(new Compiler());

        $this->assertEquals(null, $extension->resolveExpression(null));
    }

    public function testCompileStatement()
    {
        $extension = new StrExtension(new Compiler());

        $this->assertEquals(null, $extension->compileStatement(null));
    }
}
