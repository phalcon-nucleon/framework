<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler;

/**
 * A Volt extension (`view.extensions`). Volt calls each method on every function, filter, expression and
 * statement it compiles: return the PHP code, or `null` to let Volt (or another extension) compile it.
 *
 * Overrides type their parameters, not their return.
 */
abstract class ExtensionExtend extends Extending
{
    /**
     * @param string       $name          Function called in the template
     * @param string       $arguments     Compiled arguments
     * @param array<mixed>|null $funcArguments Arguments, as parsed by Volt
     *
     * @return string|null
     */
    public function compileFunction(string $name, string $arguments, ?array $funcArguments)
    {
        return null;
    }

    /**
     * @param array<mixed>|null $funcArguments
     *
     * @return string|null
     */
    public function compileFilter(string $name, string $arguments, ?array $funcArguments)
    {
        return null;
    }

    /**
     * @param array<mixed> $expr
     *
     * @return string|null
     */
    public function resolveExpression(array $expr)
    {
        return null;
    }

    /**
     * @param array<mixed> $statement
     *
     * @return string|null
     */
    public function compileStatement(array $statement)
    {
        return null;
    }
}
