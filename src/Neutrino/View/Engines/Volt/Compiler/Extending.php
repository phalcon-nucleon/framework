<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler;

use Phalcon\Mvc\View\Engine\Volt\Compiler;

/**
 * Base of the Volt extensions, functions and filters: built with the compiler.
 */
abstract class Extending
{
    public function __construct(protected Compiler $compiler) {}

    /**
     * The parsed expression of an argument (`$exprArgs[$index]['expr']`), if given.
     *
     * @param array<mixed>|null $exprArgs
     *
     * @return array<mixed>|null
     */
    protected static function argument(?array $exprArgs, int $index): ?array
    {
        $argument = $exprArgs[$index] ?? null;
        $expr = is_array($argument) ? $argument['expr'] ?? null : null;

        return is_array($expr) ? $expr : null;
    }

    /**
     * The compiled PHP code of an argument, if given.
     *
     * @param array<mixed>|null $exprArgs
     */
    protected function compileArgument(?array $exprArgs, int $index): ?string
    {
        $expr = self::argument($exprArgs, $index);

        return $expr === null ? null : $this->compiler->expression($expr);
    }
}
