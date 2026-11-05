<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler\Functions;

use Neutrino\View\Engines\Volt\Compiler\FunctionExtend;

/**
 * `{{ route('name') }}`, `{{ route('name', {'id': 1}) }}`, `{{ route('name', {'id': 1}, {'page': 2}) }}`: URL of a
 * named route (`url->get()`), with its parameters and a query string.
 */
class RouteFunction extends FunctionExtend
{
    private const int VOLT_ARRAY = 360;

    public function compileFunction(string $resolvedArgs, ?array $exprArgs): string
    {
        $parameters = self::argument($exprArgs, 1)['left'] ?? null;

        $route = $this->compiler->expression([
            'type' => self::VOLT_ARRAY,
            'left' => [['name' => 'for', 'expr' => self::argument($exprArgs, 0) ?? []], ...(is_array($parameters) ? $parameters : [])],
        ]);

        $query = $this->compileArgument($exprArgs, 2);
        $query = $query === null ? '' : ', ' . $query;

        return '$this->url->get(' . $route . $query . ')';
    }
}
