<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler\Filters;

use Neutrino\View\Engines\Volt\Compiler\FilterExtend;

/**
 * `{{ s|split(',') }}` (`explode()`), `{{ s|split(',', 2) }}` (limit), `{{ s|split }}` / `{{ s|split('', 3) }}`
 * (`str_split()`, chunks of 1 or 3 characters).
 */
class SplitFilter extends FilterExtend
{
    public function compileFilter(string $resolvedArgs, ?array $exprArgs): string
    {
        $value = $this->compileArgument($exprArgs, 0) ?? $resolvedArgs;
        $separator = $this->compileArgument($exprArgs, 1) ?? "''";
        $limit = $this->compileArgument($exprArgs, 2);

        if ($separator === "''" || $separator === '""') {
            return 'str_split(' . $value . ', ' . ($limit ?? '1') . ')';
        }

        return 'explode(' . $separator . ', ' . $value . ($limit === null ? '' : ', ' . $limit) . ')';
    }
}
