<?php

namespace Luxury\Support\DesignPatterns\Strategy;

interface StrategyInterface
{
    /**
     * Return / Change current adapter.
     *
     * @param string|null $use
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function uses($use = null);
}