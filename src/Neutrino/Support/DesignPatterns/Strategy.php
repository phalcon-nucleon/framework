<?php

namespace Neutrino\Support\DesignPatterns;

use Neutrino\Support\DesignPatterns\Strategy\StrategyInterface;
use Neutrino\Support\DesignPatterns\Strategy\StrategyTrait;
use Phalcon\Di\Injectable;

/**
 * Class Strategy
 *
 * Strategy Design Pattern
 *
 *  @package Neutrino
 */
abstract class Strategy extends Injectable implements StrategyInterface
{
    use StrategyTrait;
}
