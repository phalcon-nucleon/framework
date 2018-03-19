<?php

namespace Neutrino\View\Engines\Volt\Compiler\Filters;

use Neutrino\View\Engines\Volt\Compiler\FilterExtend;

/**
 * Class SliceFilter
 *
 * @package Neutrino\View\Engines\Volt\Compiler\Filters
 */
class SliceFilter extends FilterExtend
{

    /**
     * @param string $resolvedArgs
     * @param array  $exprArgs
     *
     * @return string|null
     */
    public function compileFilter($resolvedArgs, $exprArgs)
    {
        return 'array_slice(' . $resolvedArgs . ')';
    }
}
