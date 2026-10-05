<?php

declare(strict_types=1);

namespace Neutrino\Support;

/**
 * Function helpers.
 */
final class Func
{
    /**
     * Call the given callback with the given value then return the value.
     *
     * @template T
     *
     * @param T $value
     * @param callable(T): mixed $callback
     *
     * @return T
     */
    public static function tap(mixed $value, callable $callback): mixed
    {
        $callback($value);

        return $value;
    }
}
