<?php

declare(strict_types=1);

namespace Neutrino\Support;

use Closure;
use stdClass;

/**
 * Object helpers, with "dot" notation support for nested properties.
 */
final class Obj
{
    /**
     * Set a property using "dot" notation, only if it is not already set.
     *
     * @param array<array-key>|string|int|null $key
     */
    public static function fill(object &$target, array|string|int|null $key, mixed $value): object
    {
        return self::set($target, $key, $value, false);
    }

    /**
     * Get a property (no "dot" notation). Null values are returned.
     */
    public static function read(?object $object, ?string $property, mixed $default = null): mixed
    {
        if ($object === null || $property === null) {
            return self::value($default);
        }

        if (isset($object->$property) || property_exists($object, $property)) {
            return $object->$property;
        }

        return self::value($default);
    }

    /**
     * Get a property (no "dot" notation). Null values return the default.
     */
    public static function fetch(?object $object, ?string $property, mixed $default = null): mixed
    {
        if ($object === null || $property === null) {
            return self::value($default);
        }

        return $object->$property ?? self::value($default);
    }

    /**
     * Get a property using "dot" notation.
     *
     * @param array<array-key>|string|int|null $key
     */
    public static function get(mixed $target, array|string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null || !is_object($target)) {
            return self::value($default);
        }

        if (!is_array($key)) {
            if (isset($target->{$key}) || property_exists($target, (string) $key)) {
                return $target->{$key};
            }

            $key = explode('.', (string) $key);
        }

        foreach ($key as $segment) {
            if (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return self::value($default);
            }
        }

        return $target;
    }

    /**
     * Set a property using "dot" notation. Missing intermediate properties
     * are created as stdClass objects.
     *
     * @param array<array-key>|string|int|null $key
     */
    public static function set(object &$target, array|string|int|null $key, mixed $value, bool $overwrite = true): object
    {
        if ($key === null) {
            return $target;
        }

        if (!is_array($key)) {
            if (isset($target->{$key}) || property_exists($target, (string) $key)) {
                if ($overwrite) {
                    $target->{$key} = self::value($value);
                }

                return $target;
            }

            $key = explode('.', (string) $key);
        }

        $keep = $target;

        while (count($key) > 1) {
            $segment = array_shift($key);

            if (!isset($target->{$segment}) || (!is_object($target->{$segment}) && $overwrite)) {
                $target->{$segment} = new stdClass();
            } elseif (!is_object($target->{$segment})) {
                return $target;
            }

            $target = &$target->{$segment};
        }

        $segment = array_shift($key);

        if (!isset($target->{$segment}) || $overwrite) {
            $target->{$segment} = self::value($value);
        }

        return $keep;
    }

    /**
     * Return the default value of the given value (closures are called).
     */
    public static function value(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }
}
