<?php

namespace Neutrino\View\Engines\Volt\Compiler\Extensions;

use Neutrino\View\Engines\Volt\Compiler\ExtensionExtend;

/**
 * Class PhpFunctions
 *
 * @package Neutrino\View\Engine\Extensions
 */
class PhpFunctionExtension extends ExtensionExtend
{
    /**
     * This method is called on any attempt to compile a function call
     *
     * @param $name
     * @param $arguments
     * @param $funcArguments
     *
     * @return null|string
     */
    public function compileFunction($name, $arguments, $funcArguments)
    {
        if (function_exists($name)) {
            return $name . '(' . $arguments . ')';
        }

        return null;
    }

    public function compileFilter($name, $arguments, $funcArguments)
    {
    }

    public function resolveExpression($expr)
    {
    }

    public function compileStatement($statement)
    {
    }
}
