<?php

namespace Neutrino\View\Engines\Volt\Compiler\Extensions;

use Neutrino\View\Engines\Volt\Compiler\ExtensionExtend;

/**
 * Class CsrfExtension
 *
 * @package Neutrino\View\Engines\Volt\Compiler\Extensions
 */
class CsrfExtension extends ExtensionExtend
{

    public function compileFunction($name, $arguments, $funcArguments)
    {
        if ($name === 'csrf_field') {
            return '$this->tag->hiddenField([$this->security->getTokenKey(), \'value\' => $this->security->getToken()])';
        }
        if ($name === 'csrf_token') {
            return '$this->security->getToken()';
        }
        if ($name === 'csrf_key') {
            return '$this->security->getTokenKey()';
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
