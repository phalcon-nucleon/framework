<?php

declare(strict_types=1);

namespace Test\Support;

use ReflectionObject;
use IteratorAggregate;
use Neutrino\Support\Fluent;
use PHPUnit\Framework\TestCase;

final class FluentTest extends TestCase
{
    public function testAttributesAreSetByConstructor(): void
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent($array);

        $refl = new ReflectionObject($fluent);
        $attributes = $refl->getProperty('attributes');
        $attributes->setAccessible(true);

        $this->assertEquals($array, $attributes->getValue($fluent));
        $this->assertEquals($array, $fluent->getAttributes());
    }

    public function testAttributesAreSetByConstructorGivenStdClass(): void
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent((object) $array);

        $refl = new ReflectionObject($fluent);
        $attributes = $refl->getProperty('attributes');
        $attributes->setAccessible(true);

        $this->assertEquals($array, $attributes->getValue($fluent));
        $this->assertEquals($array, $fluent->getAttributes());
    }

    public function testAttributesAreSetByConstructorGivenArrayIterator(): void
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent(new FluentArrayIteratorStub($array));

        $refl = new ReflectionObject($fluent);
        $attributes = $refl->getProperty('attributes');
        $attributes->setAccessible(true);

        $this->assertEquals($array, $attributes->getValue($fluent));
        $this->assertEquals($array, $fluent->getAttributes());
    }

    public function testGetMethodReturnsAttribute(): void
    {
        $fluent = new Fluent(['name' => 'Taylor']);

        $this->assertEquals('Taylor', $fluent->get('name'));
        $this->assertEquals('Default', $fluent->get('foo', 'Default'));
        $this->assertEquals('Taylor', $fluent->name);
        $this->assertNull($fluent->foo);
    }

    public function testMagicMethodsCanBeUsedToSetAttributes(): void
    {
        $fluent = new Fluent();

        $fluent->name = 'Taylor';
        $fluent->developer();
        $fluent->age(25);

        $this->assertEquals('Taylor', $fluent->name);
        $this->assertTrue($fluent->developer);
        $this->assertEquals(25, $fluent->age);
        $this->assertInstanceOf(Fluent::class, $fluent->programmer());
    }

    public function testIssetMagicMethod(): void
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent($array);

        $this->assertTrue(isset($fluent->name));

        unset($fluent->name);

        $this->assertFalse(isset($fluent->name));
    }

    public function testToArrayReturnsAttribute(): void
    {
        $array = ['name' => 'Taylor', 'age' => 25];
        $fluent = new Fluent($array);

        $this->assertEquals($array, $fluent->toArray());
    }

    public function testToJsonEncodesTheToArrayResult(): void
    {
        $fluent = $this->getMockBuilder(Fluent::class)->onlyMethods(['toArray'])->getMock();
        $fluent->expects($this->once())->method('toArray')->willReturn(['foo']);
        $results = $fluent->toJson();

        $this->assertJsonStringEqualsJsonString(json_encode(['foo']), $results);
    }

    public function testArrayAccessAndIteration(): void
    {
        $fluent = new Fluent(['a' => 1]);
        $fluent['b'] = 2;
        $fluent[] = 3;

        $this->assertTrue(isset($fluent['a']));
        $this->assertSame(2, $fluent['b']);
        $this->assertSame(['a' => 1, 'b' => 2, 0 => 3], iterator_to_array($fluent));

        unset($fluent['a']);
        $this->assertFalse(isset($fluent['a']));
        $this->assertSame('default', $fluent->get('a', static fn() => 'default'));
    }
}

/**
 * @implements IteratorAggregate<string, mixed>
 */
class FluentArrayIteratorStub implements IteratorAggregate
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(protected array $items = []) {}

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
