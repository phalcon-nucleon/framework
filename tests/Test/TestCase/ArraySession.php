<?php

declare(strict_types=1);

namespace Test\TestCase;

use Phalcon\Session\Manager;
use Phalcon\Session\ManagerInterface;

/**
 * A started session kept in memory: PHPUnit has already sent its output, a real session cannot start.
 */
final class ArraySession extends Manager
{
    /** @var array<string, mixed> */
    public array $data = [];

    public int $regenerated = 0;

    public function start(): bool
    {
        return true;
    }

    public function exists(): bool
    {
        return true;
    }

    public function get(string $key, $defaultValue = null, bool $remove = false): mixed
    {
        $value = $this->data[$key] ?? $defaultValue;

        if ($remove) {
            unset($this->data[$key]);
        }

        return $value;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerateId(bool $deleteOldSession = true): ManagerInterface
    {
        $this->regenerated++;

        return $this;
    }

    public function destroy(): void
    {
        $this->data = [];
    }
}
