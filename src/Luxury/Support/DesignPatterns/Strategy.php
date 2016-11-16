<?php

namespace Luxury\Support\DesignPatterns;

use Luxury\Support\DesignPatterns\Strategy\StrategyInterface;
use Luxury\Support\DesignPatterns\Strategy\StrategyTrait;
use Phalcon\Di\Injectable;

/**
 * Class Strategy
 *
 * Strategy Design Pattern
 *
 * @package Luxury
 */
abstract class Strategy extends Injectable implements StrategyInterface
{
    use StrategyTrait;
}
