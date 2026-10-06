<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler;

/**
 * A Volt filter (`view.filters`, `name => class`).
 */
abstract class FilterExtend extends Extending
{
    /**
     * @param string       $resolvedArgs Compiled arguments, the filtered value first
     * @param array<mixed>|null $exprArgs Arguments, as parsed by Volt (`null` without parentheses)
     *
     * @return string The PHP expression
     */
    abstract public function compileFilter(string $resolvedArgs, ?array $exprArgs);
}
