<?php

declare(strict_types=1);

namespace Test\Support;

use Neutrino\Support\Reflection;
use PHPUnit\Framework\TestCase;
use ReflectionException;

final class ReflectionTest extends TestCase
{
    protected function tearDown(): void
    {
        Reflection::set(StubReflectionParent::class, 'counter', 0);
    }

    public function testGet(): void
    {
        $object = new StubReflectionChild();

        $this->assertSame('child private', Reflection::get($object, 'private'));
        $this->assertSame('parent private', Reflection::get($object, 'parentPrivate'));
        $this->assertSame('protected', Reflection::get($object, 'protected'));
        $this->assertSame('readonly', Reflection::get($object, 'readonly'));
        $this->assertSame(0, Reflection::get(StubReflectionChild::class, 'counter'));
    }

    public function testSet(): void
    {
        $object = new StubReflectionChild();

        Reflection::set($object, 'private', 'a');
        Reflection::set($object, 'parentPrivate', 'b');
        Reflection::set(StubReflectionChild::class, 'counter', 3);

        $this->assertSame('a', Reflection::get($object, 'private'));
        $this->assertSame('b', Reflection::get($object, 'parentPrivate'));
        $this->assertSame(3, Reflection::get(StubReflectionParent::class, 'counter'));
    }

    public function testInvoke(): void
    {
        $object = new StubReflectionChild();

        $this->assertSame('child:x', Reflection::invoke($object, 'privateMethod', 'x'));
        $this->assertSame('parent:y', Reflection::invoke($object, 'parentMethod', 'y'));
        $this->assertSame(1, Reflection::invoke(StubReflectionChild::class, 'increment'));
        $this->assertSame(2, Reflection::invoke($object, 'increment'));
    }

    public function testProperties(): void
    {
        $names = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), Reflection::properties(new StubReflectionChild()));

        sort($names);

        $this->assertSame(['counter', 'parentPrivate', 'private', 'protected', 'readonly'], $names);
    }

    public function testUnknownMember(): void
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Property Test\Support\StubReflectionChild::$unknown does not exist');

        Reflection::get(new StubReflectionChild(), 'unknown');
    }

    public function testUnknownMethod(): void
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Method Test\Support\StubReflectionChild::unknown() does not exist');

        Reflection::invoke(new StubReflectionChild(), 'unknown');
    }

    public function testInstanceMemberOfAClass(): void
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Test\Support\StubReflectionChild::private is not static: pass an instance.');

        Reflection::get(StubReflectionChild::class, 'private');
    }
}

class StubReflectionParent
{
    private static int $counter = 0;

    private string $parentPrivate = 'parent private';

    protected string $protected = 'protected';

    private static function increment(): int
    {
        return ++self::$counter;
    }

    private function parentMethod(string $value): string
    {
        return 'parent:' . $value;
    }
}

final class StubReflectionChild extends StubReflectionParent
{
    private string $private = 'child private';

    public function __construct(public readonly string $readonly = 'readonly') {}

    private function privateMethod(string $value): string
    {
        return 'child:' . $value;
    }
}
