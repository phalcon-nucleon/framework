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
            return '$this->tag->hiddenField(["_csrf_token", \'id\' => null, \'value\' => $this->security->getSessionToken() ?: $this->security->getToken()])';
        }
        if ($name === 'csrf_token') {
            return '$this->security->getSessionToken() ?: $this->security->getToken()';
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
