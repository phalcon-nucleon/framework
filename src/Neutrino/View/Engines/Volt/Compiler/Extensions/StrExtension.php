<?php

namespace Neutrino\View\Engines\Volt\Compiler\Extensions;

use Neutrino\Debug\Reflexion;
use Neutrino\Support\Str;
use Neutrino\View\Engines\Volt\Compiler\ExtensionExtend;

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

        if (method_exists(Str::class, $name) && Reflexion::getReflectionMethod(Str::class, $name)->isPublic()) {
            return Str::class . '::' . $name . '(' . $arguments . ')';
        }

        return null;
    }

    public function compileFilter($name, $arguments, $funcArguments)
    {
        switch ($name) {
            case 'slug':
                return Str::class . '::slug(' . $arguments . ')';
            case 'limit':
                return Str::class . '::limit(' . $arguments . ')';
            case 'words':
                return Str::class . '::words(' . $arguments . ')';
        }

        return null;
    }

    public function resolveExpression($expr)
    {
    }

    public function compileStatement($statement)
    {
    }
}
