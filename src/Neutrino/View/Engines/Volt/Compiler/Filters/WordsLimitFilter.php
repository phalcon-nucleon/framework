<?php

namespace Neutrino\View\Engines\Volt\Compiler\Filters;

use Neutrino\Support\Str;
use Neutrino\View\Engines\Volt\Compiler\FilterExtend;

/**
 * Class WordsLimitFilter
 *
 * @package Neutrino\View\Engine\Compiler\Filters
 *
 * @see \Neutrino\Support\Str::words()
 */
class WordsLimitFilter extends FilterExtend
{

    /**
     * @param string $resolvedArgs
     * @param array  $exprArgs
     *
     * @return string|null
     */
    public function compileFilter($resolvedArgs, $exprArgs)
    {
        return Str::class . '::words(' . $resolvedArgs . ')';
    }
}