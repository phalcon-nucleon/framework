<?php

declare(strict_types=1);

namespace Neutrino\Dotconst\Extensions;

use LogicException;

/**
 * A dynamic value of a `.const.ini` file, written `@{identifier}`.
 */
abstract class Extension
{
    /**
     * Regular expression (without delimiters) matched after the `@`.
     */
    protected string $identifier;

    public function __construct()
    {
        if (!isset($this->identifier) || $this->identifier === '') {
            throw new LogicException(static::class . '::$identifier can\'t be empty');
        }
    }

    final public function identify(mixed $value): bool
    {
        return is_string($value) && preg_match("#^@{$this->identifier}@?#", $value) === 1;
    }

    /**
     * @return array<string>
     */
    protected function match(string $value): array
    {
        preg_match("#^@{$this->identifier}@?#", $value, $match);

        return $match;
    }

    /**
     * Value of the constant when the ini files are read at runtime.
     */
    abstract public function parse(string $value, string $basePath): mixed;

    /**
     * PHP expression of the constant, written in the compiled file.
     *
     * @param string $basePath    Directory of the ini files
     * @param string $compilePath Directory of the compiled file
     */
    abstract public function compile(string $value, string $basePath, string $compilePath): string;

    /**
     * Whether {@see Extension::compile()} returns a constant expression,
     * which allows `const X = ...;` instead of `define()`.
     */
    public function isConstantExpression(): bool
    {
        return true;
    }
}
