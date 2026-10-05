<?php

declare(strict_types=1);

namespace Test\Design;

use ArrayObject;
use Neutrino\Support\DesignPatterns\Strategy;
use Neutrino\Support\DesignPatterns\Strategy\MagicCallStrategyTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplStack;

final class StrategyTest extends TestCase
{
    public function testUnsupportedAdapter(): void
    {
        $instance = new StubWrongStrategy();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(StubWrongStrategy::class . ' : default unsupported. ');

        $instance->uses('default');
    }

    public function testNoDefaultAdapter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(StubWrongStrategy::class . ' : no default adapter.');

        (new StubWrongStrategy())->uses();
    }

    public function testDefaultAndSwitch(): void
    {
        $instance = new StubGoodStrategy();

        $default = $instance->uses();
        $this->assertInstanceOf(ArrayObject::class, $default);
        $this->assertSame($default, $instance->uses());

        $stack = $instance->uses(SplStack::class);
        $this->assertInstanceOf(SplStack::class, $stack);
        $this->assertSame($stack, $instance->uses());
        $this->assertSame($default, $instance->uses(ArrayObject::class));
    }

    public function testMagicCallIsForwardedToTheAdapter(): void
    {
        $instance = new StubGoodStrategy();

        $this->assertSame(0, $instance->count());

        $this->expectException(\BadMethodCallException::class);

        $instance->unknown();
    }
}

class StubWrongStrategy extends Strategy
{
    protected array $supported = [];
}

/**
 * @method int count()
 * @method mixed unknown()
 */
class StubGoodStrategy extends Strategy
{
    use MagicCallStrategyTrait;

    protected array $supported = [ArrayObject::class, SplStack::class];

    protected ?string $default = ArrayObject::class;
}
