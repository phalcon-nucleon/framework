<?php

declare(strict_types=1);

namespace Neutrino\Support\DesignPatterns;

use Neutrino\Support\DesignPatterns\Strategy\StrategyInterface;
use Neutrino\Support\DesignPatterns\Strategy\StrategyTrait;
use Phalcon\Di\Injectable;

/**
 * Strategy design pattern: delegates to one of the supported adapters.
 */
abstract class Strategy extends Injectable implements StrategyInterface
{
    use StrategyTrait;
}
