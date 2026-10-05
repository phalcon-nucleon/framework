<?php

declare(strict_types=1);

namespace Neutrino\Config;

use Phalcon\Config\Config as PhalconConfig;

/**
 * Application configuration: a `Phalcon\Config\Config` with fast reads.
 *
 * Phalcon 5 resolves every read through its case-insensitive collection (about 0.4 µs per level).
 * Here a read with the exact key goes straight to the stored array; any other read
 * (different case, missing key, cast) falls back to Phalcon. Nested arrays are instances of this class too.
 *
 * Differences with Phalcon:
 * - writing a key with another case replaces the previous spelling, where Phalcon keeps both
 *   (and returns both from `toArray()`, and `remove()` only removes the last one);
 * - `path()` through a scalar value returns the default value, where Phalcon fails.
 *
 * @phpstan-consistent-constructor
 */
class Config extends PhalconConfig
{
    public function __get(string $element): mixed
    {
        return $this->data[$element] ?? parent::__get($element);
    }

    public function __isset(string $element): bool
    {
        return isset($this->data[$element]) || parent::__isset($element);
    }

    public function offsetGet(mixed $element): mixed
    {
        return (is_string($element) || is_int($element)) && isset($this->data[$element])
            ? $this->data[$element]
            : parent::offsetGet($element);
    }

    public function offsetExists(mixed $element): bool
    {
        return ((is_string($element) || is_int($element)) && isset($this->data[$element])) || parent::offsetExists($element);
    }

    public function get(string $element, mixed $defaultValue = null, ?string $cast = null): mixed
    {
        if ($cast === null && isset($this->data[$element])) {
            return $this->data[$element];
        }

        return parent::get($element, $defaultValue, $cast);
    }

    public function has(string $element): bool
    {
        return isset($this->data[$element]) || parent::has($element);
    }

    public function path(string $path, mixed $defaultValue = null, ?string $delimiter = null): mixed
    {
        // As Phalcon: a key containing the delimiter is looked up as a whole first.
        if (isset($this->data[$path])) {
            return $this->data[$path];
        }

        $delimiter = ($delimiter ?? $this->getPathDelimiter()) ?: '.';

        if ($this->insensitive && str_contains($path, $delimiter) && isset($this->lowerKeys[mb_strtolower($path)])) {
            return parent::get($path);
        }

        $value = $this;

        foreach (explode($delimiter, $path) as $key) {
            if ($value instanceof self && isset($value->data[$key])) {
                $value = $value->data[$key];
            } elseif ($value instanceof PhalconConfig && $value->has($key)) {
                $value = $value->get($key);
            } else {
                // Phalcon fails when the path goes through a scalar value: the default is returned instead.
                return $defaultValue;
            }
        }

        return $value;
    }

    protected function setData(mixed $element, mixed $value): void
    {
        if (is_array($value)) {
            $value = new static($value, $this->insensitive, $this->strictNull, $this->type);
        }

        // Phalcon keeps the value of each spelling of a key and reads the last one written:
        // the previous spelling is removed so that a direct read of the array cannot return a stale value.
        // Phalcon lowers keys with mb_strtolower(), see Collection::processKey().
        if ($this->insensitive && (is_string($element) || is_int($element))) {
            $previous = $this->lowerKeys[mb_strtolower((string) $element)] ?? null;

            if ($previous !== null && $previous !== $element) {
                unset($this->data[$previous]);
            }
        }

        parent::setData($element, $value);
    }
}
