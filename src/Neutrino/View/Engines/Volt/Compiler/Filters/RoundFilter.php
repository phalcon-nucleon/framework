<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler\Filters;

use Neutrino\View\Engines\Volt\Compiler\FilterExtend;

/**
 * `{{ x|round }}`, `{{ x|round(2) }}`, `{{ x|round('floor') }}`, `{{ x|round(2, 'ceil') }}`.
 */
class RoundFilter extends FilterExtend
{
    private const int VOLT_STRING = 260;

    public function compileFilter(string $resolvedArgs, ?array $exprArgs): string
    {
        $value = $this->compileArgument($exprArgs, 0) ?? $resolvedArgs;
        $first = self::argument($exprArgs, 1);

        if ($first !== null && ($first['type'] ?? null) === self::VOLT_STRING) {
            return match ($first['value'] ?? null) {
                'floor' => "floor($value)",
                'ceil'  => "ceil($value)",
                default => "round($value)",
            };
        }

        $precision = $this->compileArgument($exprArgs, 1) ?? '0';

        return match (self::argument($exprArgs, 2)['value'] ?? null) {
            'floor' => "floor($value * (10 ** $precision)) / (10 ** $precision)",
            'ceil'  => "ceil($value * (10 ** $precision)) / (10 ** $precision)",
            default => "round($value, $precision)",
        };
    }
}
