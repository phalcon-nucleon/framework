<?php

declare(strict_types=1);

namespace Neutrino\Dotconst\Extensions;

/**
 * `@php/const:NAME[@suffix]`: value of a PHP constant (`PHP_INT_MAX`, `Foo::BAR`), with an optional suffix.
 */
final class PhpConst extends Extension
{
    protected string $identifier = 'php/const:([\w:\\\\]+)(?:@(.+))?';

    public function parse(string $value, string $basePath): mixed
    {
        $match = $this->match($value);

        $constant = constant($match[1]);

        if (!isset($match[2])) {
            return $constant;
        }

        if (!is_scalar($constant) && $constant !== null) {
            throw new \UnexpectedValueException("Constant {$match[1]} can't be suffixed: it is not a scalar.");
        }

        return $constant . $match[2];
    }

    public function compile(string $value, string $basePath, string $compilePath): string
    {
        $match = $this->match($value);

        if (isset($match[2])) {
            return '\\' . ltrim($match[1], '\\') . ' . ' . var_export($match[2], true);
        }

        return '\\' . ltrim($match[1], '\\');
    }
}
