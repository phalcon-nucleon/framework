<?php

declare(strict_types=1);

namespace Neutrino\Support\DesignPatterns\Strategy;

use RuntimeException;

trait StrategyTrait
{
    /**
     * Supported adapters.
     *
     * @var list<string>
     */
    protected array $supported = [];

    /**
     * Default adapter.
     */
    protected ?string $default = null;

    /**
     * Adapters already built, by name.
     *
     * @var array<string, object>
     */
    private array $adapters = [];

    /**
     * Current adapter.
     */
    private ?object $adapter = null;

    /**
     * Returns the current adapter, after switching to `$use` when given.
     *
     * @throws RuntimeException When `$use` is not supported
     */
    public function uses(?string $use = null): object
    {
        if ($use !== null && $use !== '') {
            if (!in_array($use, $this->supported, true)) {
                throw new RuntimeException(static::class . " : $use unsupported. ");
            }

            $this->adapter = $this->adapters[$use] ??= $this->make($use);
        }

        if ($this->adapter === null) {
            if ($this->default === null || $this->default === '') {
                throw new RuntimeException(static::class . ' : no default adapter.');
            }

            $this->adapter = $this->adapters[$this->default] ??= $this->make($this->default);
        }

        return $this->adapter;
    }

    /**
     * Builds an adapter.
     */
    protected function make(string $use): object
    {
        return new $use();
    }
}
