<?php

declare(strict_types=1);

/**
 * Laravel 5.4 Fluent Class
 *
 * @see https://github.com/illuminate/support/blob/401bb82931e22bb8e8de727f3bde9cff7d186821/Fluent.php
 */

namespace Neutrino\Support\Fluent;

use Neutrino\Support\Obj;

/**
 * Implementation of {@see Fluentable}.
 */
trait Fluentize
{
    /**
     * All of the attributes set on the container.
     *
     * @var array<array-key, mixed>
     */
    protected array $attributes = [];

    /**
     * @param iterable<array-key, mixed>|object $attributes
     */
    public function __construct(iterable|object $attributes = [])
    {
        /** @var iterable<array-key, mixed> $attributes */
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public function get(string|int $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return Obj::value($default);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    public function current(): mixed
    {
        return current($this->attributes);
    }

    public function next(): void
    {
        next($this->attributes);
    }

    public function key(): string|int|null
    {
        return key($this->attributes);
    }

    public function valid(): bool
    {
        return key($this->attributes) !== null;
    }

    public function rewind(): void
    {
        reset($this->attributes);
    }

    /**
     * @param array<int, mixed> $parameters
     */
    public function __call(string $method, array $parameters): static
    {
        $this->attributes[$method] = count($parameters) > 0 ? $parameters[0] : true;

        return $this;
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->offsetSet($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->offsetExists($key);
    }

    public function __unset(string $key): void
    {
        $this->offsetUnset($key);
    }
}
