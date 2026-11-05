<?php

declare(strict_types=1);

namespace Neutrino\Support;

use ArrayAccess;
use InvalidArgumentException;

/**
 * Array helpers, with "dot" notation support for nested keys.
 */
final class Arr
{
    /**
     * Determine whether the given value is array accessible.
     */
    public static function accessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function add(array $array, string $key, mixed $value): array
    {
        if (self::get($array, $key) === null) {
            self::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param iterable<mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function collapse(iterable $array): array
    {
        $results = [];

        foreach ($array as $values) {
            if (is_array($values)) {
                $results[] = $values;
            }
        }

        return array_merge([], ...$results);
    }

    /**
     * Divide an array into two arrays: one with keys and the other with values.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array{0: list<array-key>, 1: list<mixed>}
     */
    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<string, mixed>
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && $value !== []) {
                $results[] = self::dot($value, $prepend . $key . '.');
            } else {
                $results[] = [$prepend . $key => $value];
            }
        }

        return array_merge([], ...$results);
    }

    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param array<array-key, mixed>         $array
     * @param array-key|array<array-key>|null $keys
     *
     * @return array<array-key, mixed>
     */
    public static function except(array $array, int|string|array|null $keys): array
    {
        self::forget($array, $keys);

        return $array;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param array<array-key, mixed>|ArrayAccess<array-key, mixed> $array
     */
    public static function exists(array|ArrayAccess $array, int|string $key): bool
    {
        if (is_array($array)) {
            return array_key_exists($key, $array);
        }

        return $array->offsetExists($key);
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * The callback receives ($key, $value).
     *
     * @param iterable<mixed> $array
     */
    public static function first(iterable $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            foreach ($array as $item) {
                return $item;
            }

            return Obj::value($default);
        }

        foreach ($array as $key => $value) {
            if ($callback($key, $value)) {
                return $value;
            }
        }

        return Obj::value($default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * The callback receives ($key, $value).
     *
     * @param array<array-key, mixed> $array
     */
    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $array === [] ? Obj::value($default) : $array[array_key_last($array)];
        }

        return self::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param iterable<mixed> $array
     *
     * @return list<mixed>
     */
    public static function flatten(iterable $array, float|int $depth = INF): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                array_push($result, ...array_values($item));
            } else {
                array_push($result, ...self::flatten($item, $depth - 1));
            }
        }

        return $result;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param array<array-key, mixed>         $array
     * @param array-key|array<array-key>|null $keys
     */
    public static function forget(array &$array, int|string|array|null $keys): void
    {
        $original = &$array;

        foreach ((array) $keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.', (string) $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Get an item from an array (no "dot" notation). Null values are returned.
     *
     * @param array<array-key, mixed>|ArrayAccess<array-key, mixed> $array
     */
    public static function read(array|ArrayAccess $array, int|string|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return Obj::value($default);
        }

        return self::exists($array, $key) ? $array[$key] : Obj::value($default);
    }

    /**
     * Get an item from an array (no "dot" notation). Null values return the default.
     *
     * @param array<array-key, mixed>|ArrayAccess<array-key, mixed> $array
     */
    public static function fetch(array|ArrayAccess $array, int|string|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return Obj::value($default);
        }

        return $array[$key] ?? Obj::value($default);
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param array<array-key>|string|int|null $key
     */
    public static function get(mixed $array, array|string|int|null $key, mixed $default = null): mixed
    {
        if (!is_array($array) && !$array instanceof ArrayAccess) {
            return Obj::value($default);
        }

        if ($key === null) {
            return $array;
        }

        if (!is_array($key)) {
            if (self::exists($array, $key)) {
                return $array[$key];
            }

            $key = explode('.', (string) $key);
        }

        foreach ($key as $segment) {
            if ((is_array($array) || $array instanceof ArrayAccess) && self::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return Obj::value($default);
            }
        }

        return $array;
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param array<array-key>|string|int|null $keys
     */
    public static function has(mixed $array, array|string|int|null $keys): bool
    {
        if ($keys === null || !$array || (!is_array($array) && !$array instanceof ArrayAccess)) {
            return false;
        }

        $keys = (array) $keys;

        if ($keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            if (self::exists($array, $key)) {
                continue;
            }

            $subKeyArray = $array;

            foreach (explode('.', (string) $key) as $segment) {
                if ((is_array($subKeyArray) || $subKeyArray instanceof ArrayAccess) && self::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determines if an array is associative (its keys are not 0..n-1).
     *
     * @param array<array-key, mixed> $array
     */
    public static function isAssoc(array $array): bool
    {
        return !array_is_list($array);
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param array<array-key, mixed>    $array
     * @param array-key|array<array-key> $keys
     *
     * @return array<array-key, mixed>
     */
    public static function only(array $array, int|string|array $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param iterable<mixed>              $array
     * @param array<array-key>|string|null $value null plucks the whole item
     * @param array<array-key>|string|null $key
     *
     * @return array<array-key, mixed>
     */
    public static function pluck(iterable $array, array|string|null $value, array|string|null $key = null): array
    {
        $results = [];

        [$value, $key] = self::explodePluckParameters($value, $key);

        foreach ($array as $item) {
            $itemValue = self::get($item, $value);

            if ($key === null) {
                $results[] = $itemValue;
                continue;
            }

            $itemKey = self::get($item, $key);

            if (!is_int($itemKey) && !is_string($itemKey)) {
                if (!is_scalar($itemKey) && $itemKey !== null) {
                    throw new InvalidArgumentException('Pluck keys must be scalar values.');
                }

                $itemKey = (string) $itemKey;
            }

            $results[$itemKey] = $itemValue;
        }

        return $results;
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function prepend(array $array, mixed $value, int|string|null $key = null): array
    {
        if ($key === null) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param array<array-key, mixed> $array
     */
    public static function pull(array &$array, int|string $key, mixed $default = null): mixed
    {
        $value = self::get($array, $key, $default);

        self::forget($array, $key);

        return $value;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function set(array &$array, int|string|null $key, mixed $value): mixed
    {
        if ($key === null) {
            // The whole variable is replaced, whatever the value type (1.x behaviour).
            return $array = $value; // @phpstan-ignore parameterByRef.type, return.type
        }

        $keys = explode('.', (string) $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Recursively sort an array by keys (associative arrays) and values (lists).
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function sortRecursive(array $array, int $flags = SORT_REGULAR): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::sortRecursive($value, $flags);
            }
        }
        unset($value);

        if (self::isAssoc($array)) {
            ksort($array, $flags);
        } else {
            sort($array, $flags); // @phpstan-ignore argument.type
        }

        return $array;
    }

    /**
     * Apply a callback to each element of an array, optionally recursively.
     *
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>
     */
    public static function map(callable $callback, array $array, bool $recursive = false): array
    {
        if ($recursive) {
            $func = static function (mixed $item) use (&$func, $callback): mixed {
                return is_array($item) ? array_map($func, $item) : $callback($item);
            };

            return array_map($func, $array);
        }

        return array_map($callback, $array);
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * @param array<array-key>|string|null $value
     * @param array<array-key>|string|null $key
     *
     * @return array{0: array<array-key>|null, 1: array<array-key>|null}
     */
    public static function explodePluckParameters(array|string|null $value, array|string|null $key): array
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = $key === null || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }
}
