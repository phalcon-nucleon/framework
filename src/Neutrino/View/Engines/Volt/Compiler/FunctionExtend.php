<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler;

/**
 * A Volt function (`view.functions`, `name => class`).
 */
abstract class FunctionExtend extends Extending
{
    /**
     * @param string       $resolvedArgs Compiled arguments
     * @param array<mixed>|null $exprArgs Arguments, as parsed by Volt (`null` without parentheses)
     *
     * @return string The PHP expression
     */
    abstract public function compileFunction(string $resolvedArgs, ?array $exprArgs);
}
