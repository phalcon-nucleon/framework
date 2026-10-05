<?php

declare(strict_types=1);

namespace Test\Support;

use BadMethodCallException;
use Neutrino\Support\Traits\Macroable;
use PHPUnit\Framework\TestCase;

final class MacroableTest extends TestCase
{
    public function testRegisterMacro(): void
    {
        MacroableStub::macro('staticName', static fn() => 'Taylor');

        $this->assertTrue(MacroableStub::hasMacro('staticName'));
        $this->assertSame('Taylor', MacroableStub::staticName());
    }

    public function testRegisterMacroAndCallWithoutStatic(): void
    {
        MacroableStub::macro('instanceName', fn() => 'Taylor');

        $this->assertSame('Taylor', (new MacroableStub())->instanceName());
    }

    public function testRegisterMacroAndCallWithoutStaticCallable(): void
    {
        MacroableStub::macro('invokable', new InvokableStub());

        $this->assertSame('Taylor', (new MacroableStub())->invokable());
    }

    public function testNotFoundMethod(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method unknown does not exist.');

        (new MacroableStub())->unknown();
    }

    public function testNotFoundMethodStatic(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method unknownStatic does not exist.');

        MacroableStub::unknownStatic();
    }

    public function testWhenCallingMacroClosureIsBoundToObject(): void
    {
        MacroableStub::macro('tryInstance', function () {
            /** @var MacroableStub $this */
            return $this->protectedVariable;
        });
        MacroableStub::macro('tryStatic', static fn() => static::getProtectedStatic());

        $instance = new MacroableStub();

        $this->assertSame('instance', $instance->tryInstance());
        $this->assertSame('static', MacroableStub::tryStatic());

        MacroableStub::macro('callableFunc', [$instance, 'func']);

        $this->assertSame(123, MacroableStub::callableFunc());
    }
}

/**
 * @method static string staticName()
 * @method static string tryStatic()
 * @method static int callableFunc()
 * @method static mixed unknownStatic()
 * @method string instanceName()
 * @method string invokable()
 * @method string tryInstance()
 * @method mixed unknown()
 */
class MacroableStub
{
    use Macroable;

    protected string $protectedVariable = 'instance';

    protected static function getProtectedStatic(): string
    {
        return 'static';
    }

    public function func(): int
    {
        return 123;
    }
}

class InvokableStub
{
    public function __invoke(): string
    {
        return 'Taylor';
    }
}
