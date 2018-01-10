<?php

namespace Neutrino\View\Engine\Compiler\Filters;

use Neutrino\Support\Str;
use Neutrino\View\Engine\Compiler\FilterExtend;

/**
 * Class SlugFilter
 *
 * @package Neutrino\View\Engine\Compiler\Filters
 *
 * @see \Neutrino\Support\Str::limit()
 */
class LimitFilter extends FilterExtend
{

    /**
     * @param string $resolvedArgs
     * @param array  $exprArgs
     *
     * @return string|null
     */
    public function compileFilter($resolvedArgs, $exprArgs)
    {
        return Str::class . '::limit(' . $resolvedArgs . ')';
    }
}
