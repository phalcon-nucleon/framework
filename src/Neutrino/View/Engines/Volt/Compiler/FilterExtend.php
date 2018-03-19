<?php

namespace Neutrino\View\Engines\Volt\Compiler;

/**
 * Class FilterExtend
 *
 * @package Neutrino\View\Engine\Extending
 */
abstract class FilterExtend extends Extending
{
    /**
     * @param string $resolvedArgs
     * @param array  $exprArgs
     *
     * @return string|null
     */
    abstract public function compileFilter($resolvedArgs, $exprArgs);
}
