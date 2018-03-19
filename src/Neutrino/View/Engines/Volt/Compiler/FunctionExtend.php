<?php

namespace Neutrino\View\Engines\Volt\Compiler;

/**
 * Class FunctionExtend
 *
 * @package Neutrino\View\Engine\Extending
 */
abstract class FunctionExtend extends Extending
{
    /**
     * @param string $resolvedArgs
     * @param array  $exprArgs
     *
     * @return string|null
     */
    abstract public function compileFunction($resolvedArgs, $exprArgs);
}
