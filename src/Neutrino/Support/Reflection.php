<?php

declare(strict_types=1);

namespace Neutrino\Support;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Access to the non-public properties and methods of an object or a class (for debugging and tests).
 *
 * Private members declared by a parent class are found too.
 */
final class Reflection
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $classes = [];

    private function __construct() {}

    /**
     * The value of a property, static when `$target` is a class name.
     *
     * @param object|class-string $target
     */
    public static function get(object|string $target, string $property): mixed
    {
        $reflection = self::property($target, $property);

        return $reflection->isStatic() ? $reflection->getValue() : $reflection->getValue(self::instance($target, $property));
    }

    /**
     * Sets the value of a property, static when `$target` is a class name.
     *
     * @param object|class-string $target
     */
    public static function set(object|string $target, string $property, mixed $value): void
    {
        $reflection = self::property($target, $property);

        if ($reflection->isStatic()) {
            $reflection->setValue(null, $value);

            return;
        }

        $reflection->setValue(self::instance($target, $property), $value);
    }

    /**
     * Calls a method, static when `$target` is a class name. Arguments passed by reference are not supported.
     *
     * @param object|class-string $target
     */
    public static function invoke(object|string $target, string $method, mixed ...$arguments): mixed
    {
        $reflection = self::method($target, $method);

        return $reflection->invokeArgs($reflection->isStatic() ? null : self::instance($target, $method), $arguments);
    }

    /**
     * The properties of an object or a class, with the private ones of its parents.
     *
     * @param object|class-string $target
     *
     * @return list<ReflectionProperty>
     */
    public static function properties(object|string $target): array
    {
        $properties = [];
        $class = self::reflect($target);

        do {
            foreach ($class->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() === $class->getName() || !$property->isPrivate()) {
                    $properties[$property->getName()] ??= $property;
                }
            }
        } while ($class = $class->getParentClass());

        return array_values($properties);
    }

    /**
     * @param object|class-string $target
     *
     * @throws ReflectionException
     */
    public static function property(object|string $target, string $name): ReflectionProperty
    {
        $class = self::reflect($target);

        do {
            if ($class->hasProperty($name)) {
                return $class->getProperty($name);
            }
        } while ($class = $class->getParentClass());

        throw new ReflectionException('Property ' . self::name($target) . '::$' . $name . ' does not exist');
    }

    /**
     * @param object|class-string $target
     *
     * @throws ReflectionException
     */
    public static function method(object|string $target, string $name): ReflectionMethod
    {
        $class = self::reflect($target);

        do {
            if ($class->hasMethod($name)) {
                return $class->getMethod($name);
            }
        } while ($class = $class->getParentClass());

        throw new ReflectionException('Method ' . self::name($target) . '::' . $name . '() does not exist');
    }

    /**
     * @param object|class-string $target
     *
     * @return ReflectionClass<object>
     */
    private static function reflect(object|string $target): ReflectionClass
    {
        $class = self::name($target);

        return self::$classes[$class] ??= new ReflectionClass($class);
    }

    /**
     * @param object|class-string $target
     *
     * @return class-string
     */
    private static function name(object|string $target): string
    {
        return is_object($target) ? $target::class : $target;
    }

    /**
     * @param object|class-string $target
     *
     * @throws ReflectionException
     */
    private static function instance(object|string $target, string $member): object
    {
        if (is_string($target)) {
            throw new ReflectionException($target . '::' . $member . ' is not static: pass an instance.');
        }

        return $target;
    }
}
