<?php

declare(strict_types=1);

/**
 * Laravel 5.4 Fluent Class
 *
 * @see https://github.com/illuminate/support/blob/401bb82931e22bb8e8de727f3bde9cff7d186821/Fluent.php
 */

namespace Neutrino\Support\Fluent;

use ArrayAccess;
use Iterator;
use JsonSerializable;

/**
 * @extends ArrayAccess<array-key, mixed>
 * @extends Iterator<array-key, mixed>
 */
interface Fluentable extends ArrayAccess, Iterator, JsonSerializable
{
    /**
     * @param iterable<array-key, mixed>|object $attributes
     */
    public function __construct(iterable|object $attributes = []);

    /**
     * Gets an attribute, or `$default` (a closure is called) when it is not set.
     */
    public function get(string|int $key, mixed $default = null): mixed;

    /**
     * @return array<array-key, mixed>
     */
    public function getAttributes(): array;

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array;

    public function toJson(int $options = 0): string;

    /**
     * Sets the attribute named after the method: `$fluent->nullable()`, `$fluent->default(1)`.
     *
     * @param array<int, mixed> $parameters
     */
    public function __call(string $method, array $parameters): static;

    public function __get(string $key): mixed;

    public function __set(string $key, mixed $value): void;

    public function __isset(string $key): bool;

    public function __unset(string $key): void;
}
