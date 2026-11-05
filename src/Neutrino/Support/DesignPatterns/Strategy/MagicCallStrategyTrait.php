<?php

declare(strict_types=1);

namespace Neutrino\Support\DesignPatterns\Strategy;

use BadMethodCallException;

/**
 * Forwards unknown method calls to the current adapter.
 */
trait MagicCallStrategyTrait
{
    /**
     * @param array<int|string, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        $use = $this->uses();

        if (!method_exists($use, $name)) {
            throw new BadMethodCallException($use::class . ' doesn\'t have ' . $name . ' method.');
        }

        return $use->$name(...$arguments);
    }
}
