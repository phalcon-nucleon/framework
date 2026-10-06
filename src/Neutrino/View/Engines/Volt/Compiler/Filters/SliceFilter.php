<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler\Filters;

use Neutrino\View\Engines\Volt\Compiler\FilterExtend;

/**
 * `{{ list|slice(offset, length) }}`: `array_slice()`.
 *
 * Replaces the native Volt `slice(start, end)` filter when it is declared, whose end is inclusive and which also
 * slices strings: `[1, 2, 3, 4]|slice(0, 2)` gives `1, 2` here, `1, 2, 3` with Volt.
 */
class SliceFilter extends FilterExtend
{
    public function compileFilter(string $resolvedArgs, ?array $exprArgs): string
    {
        return 'array_slice(' . $resolvedArgs . ')';
    }
}
