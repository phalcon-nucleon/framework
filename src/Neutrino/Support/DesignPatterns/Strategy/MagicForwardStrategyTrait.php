<?php

namespace Neutrino\Support\DesignPatterns\Strategy;

trait MagicForwardStrategyTrait
{
    public function __call($name, $arguments)
    {
        $use = $this->uses();

        if (!method_exists($use, $name)) {
            throw new \BadMethodCallException(get_class($use) . ' doesn\t have ' . $name . ' method.');
        }

        return $use->$name(...$arguments);
    }
}