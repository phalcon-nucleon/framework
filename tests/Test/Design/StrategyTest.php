<?php
namespace Test\Design;

use Neutrino\Support\DesignPatterns\Strategy;
use Phalcon\Registry;
use Test\TestCase\TestCase;

/**
 * Trait StrategyTest
 *
 * @package Test\Design
 */
class StrategyTest extends TestCase
{

    public function testWrong()
    {
        $instance = new StubWrongStrategy;

        $this->setExpectedException(\RuntimeException::class,
            get_class($instance) . " : default unsupported. ");

        $instance->uses('default');
    }

    public function testMake()
    {
        $instance = new StubGoodStrategy();

        $this->assertInstanceOf(Registry::class, $instance->uses(Registry::class));
    }
}

class StubWrongStrategy extends Strategy
{
    protected $supported = [];
}

class StubGoodStrategy extends Strategy
{
    protected $supported = [
        Registry::class
    ];

    protected $default = Registry::class;
}
