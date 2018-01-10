<?php

namespace Neutrino\View\Engine\Compiler\Extensions;

use Neutrino\Support\Str;
use Neutrino\View\Engine\Compiler\ExtensionExtend;

/**
 * Class StrExtension
 *
 * Neutrino\View\Extensions
 */
class StrExtension extends ExtensionExtend
{
    /**
     * This method is called on any attempt to compile a function call
     *
     * @param $name
     * @param $arguments
     *
     * @return null|string
     */
    public function compileFunction($name, $arguments, $funcArguments)
    {
        if (!Str::startsWith($name, 'str_') || function_exists($name)) {
            return null;
        }

        $name = substr($name, 4);

        if (method_exists(Str::class, $name)) {
            return Str::class . '::' . $name . '(' . $arguments . ')';
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
