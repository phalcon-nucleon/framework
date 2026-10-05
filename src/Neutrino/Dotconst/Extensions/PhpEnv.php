<?php

declare(strict_types=1);

namespace Neutrino\Dotconst\Extensions;

/**
 * `@php/env:NAME[:default]`: environment variable, read at runtime (also from the compiled file).
 */
final class PhpEnv extends Extension
{
    protected string $identifier = 'php/env:(\w+)(?::(\w+))?';

    public function parse(string $value, string $basePath): ?string
    {
        $match = $this->match($value);

        $env = getenv($match[1]);

        return $env === false ? ($match[2] ?? null) : $env;
    }

    public function compile(string $value, string $basePath, string $compilePath): string
    {
        $match = $this->match($value);

        $name = var_export($match[1], true);
        $default = var_export($match[2] ?? null, true);

        return "(getenv($name) === false ? $default : getenv($name))";
    }

    public function isConstantExpression(): bool
    {
        return false;
    }
}
