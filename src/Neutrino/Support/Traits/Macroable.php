<?php

declare(strict_types=1);

namespace Neutrino\Support\Traits;

use BadMethodCallException;
use Closure;

/**
 * Adds methods to a class at runtime.
 *
 * @see Laravel 5.2 Illuminate\Support\Traits\Macroable
 */
trait Macroable
{
    /**
     * The registered macros.
     *
     * @var array<string, callable>
     */
    protected static array $macros = [];

    /**
     * Registers a custom macro. A closure is bound to the instance (or the class, for static calls).
     */
    public static function macro(string $name, callable $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * @param array<int|string, mixed> $parameters
     *
     * @throws BadMethodCallException
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        if (!static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = Closure::bind($macro, null, static::class) ?? $macro;
        }

        return $macro(...$parameters);
    }

    /**
     * @param array<int|string, mixed> $parameters
     *
     * @throws BadMethodCallException
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (!static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class) ?? $macro;
        }

        return $macro(...$parameters);
    }
}
