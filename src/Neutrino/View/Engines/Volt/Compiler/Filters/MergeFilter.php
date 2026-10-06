<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler\Filters;

use Neutrino\View\Engines\Volt\Compiler\FilterExtend;

/**
 * `{{ a|merge(b) }}`: `array_merge()`.
 */
class MergeFilter extends FilterExtend
{
    public function compileFilter(string $resolvedArgs, ?array $exprArgs): string
    {
        return 'array_merge(' . $resolvedArgs . ')';
    }
}
