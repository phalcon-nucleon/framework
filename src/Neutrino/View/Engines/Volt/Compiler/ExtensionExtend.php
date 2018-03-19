<?php

namespace Neutrino\View\Engines\Volt\Compiler;

/**
 * Class ExtensionInterface
 *
 * @package Neutrino\View\Engine\Extending
 */
abstract class ExtensionExtend extends Extending
{
    abstract public function compileFunction($name, $arguments, $funcArguments);

    abstract public function compileFilter($name, $arguments, $funcArguments);

    abstract public function resolveExpression($expr);

    abstract public function compileStatement($statement);
}
