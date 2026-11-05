<?php

declare(strict_types=1);

namespace Neutrino\Support\DesignPatterns\Strategy;

interface StrategyInterface
{
    /**
     * Returns the current adapter, after switching to `$use` when given.
     *
     * @throws \RuntimeException When `$use` is not supported
     */
    public function uses(?string $use = null): object;
}
