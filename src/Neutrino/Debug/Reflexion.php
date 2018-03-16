<?php

namespace Neutrino\Debug;

use Neutrino\Support\Str;

/**
 * Class Hacker
 *
 * Allows access to any methods or properties of a class.
 * Should only be used for debugging, or UnitTest.
 *
 * @package Neutrino\Support
 */
final class Reflexion
{
    private static $cache = [];

    /**
     * @param string|object $value
     *
     * @return string
     */
    private static function toClassName($value)
    {
        if (!is_string($value)) {
            $value = get_class($value);
        }

        return $value;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param string           $type
     * @param string           $name
     *
     * @return \ReflectionProperty|\ReflectionMethod
     */
    private static function retrieveReflectionElement(\ReflectionClass $reflectionClass, $type, $name)
    {
        if (!isset(self::$cache[$reflectionClass->getName()][$type][$name])) {
            try {
                /** @var \ReflectionMethod|\ReflectionProperty $reflection */
                $reflection = $reflectionClass->{'get' . Str::capitalize($type)}($name);
                $reflection->setAccessible(true);

                self::$cache[$reflectionClass->getName()][$type][$name] = $reflection;
            } catch (\ReflectionException $e) {
                if ($reflectionClass = $reflectionClass->getParentClass()) {
                    return self::retrieveReflectionElement($reflectionClass, $type, $name);
                }

                throw $e;
            }
        }

        return self::$cache[$reflectionClass->getName()][$type][$name];
    }

    /**
     * @param string|object $object
     * @param string        $type
     * @param string        $name
     *
     * @return \ReflectionMethod|\ReflectionProperty
     */
    private static function getReflectionElement($object, $type, $name)
    {
        return self::retrieveReflectionElement(self::getReflectionClass($object), $type, $name);
    }

    /**
     * @param object|string $class
     *
     * @return \ReflectionClass
     */
    public static function getReflectionClass($class)
    {
        $class = self::toClassName($class);

        if (!isset(self::$cache[$class]['class'])) {
            self::$cache[$class]['class'] = new \ReflectionClass($class);
        }

        return self::$cache[$class]['class'];
    }

    /**
     * @param string|object $object
     * @param string        $property
     *
     * @return \ReflectionProperty
     */
    public static function getReflectionProperty($object, $property)
    {
        return self::getReflectionElement($object, 'property', $property);
    }

    /**
     * @param string|object $object
     * @param string        $method
     *
     * @return \ReflectionMethod
     */
    public static function getReflectionMethod($object, $method)
    {
        return self::getReflectionElement($object, 'method', $method);
    }

    /**
     * @param string|object $object
     *
     * @return \ReflectionProperty[]
     */
    public static function getReflectionProperties($object)
    {
        return self::getReflectionClass($object)->getProperties();
    }

    /**
     * @param string|object $object
     *
     * @return \ReflectionMethod[]
     */
    public static function getReflectionMethods($object)
    {
        return self::getReflectionClass($object)->getMethods();
    }

    /**
     * @param string|object $object
     * @param string $property
     * @param mixed  $value
     */
    public static function set($object, $property, $value)
    {
        $property = self::getReflectionProperty($object, $property);

        if ($isString = is_string($object) || $property->isStatic()) {
            if (($declaringClass = $property->getDeclaringClass()->getName()) !== self::toClassName($object)) {
                self::getReflectionProperty($declaringClass, $property->getName())->setValue(null, $value);
                return;
            }

            $property->setValue(null, $value);
            return;
        }

        $property->setValue($object, $value);
    }

    /**
     * @param string|object $object
     * @param string       $property
     *
     * @return mixed
     */
    public static function get($object, $property)
    {
        $property = self::getReflectionProperty($object, $property);

        if ($isString = is_string($object) || $property->isStatic()) {
            if (($declaringClass = $property->getDeclaringClass()->getName()) !== self::toClassName($object)) {
                return self::getReflectionProperty($declaringClass, $property->getName())->getValue(null);
            }

            return $property->getValue(null);
        }

        return $property->getValue($object);
    }

    /**
     * @param string|object $object
     * @param string       $method
     * @param array        ...$params
     *
     * @return mixed
     */
    public static function invoke($object, $method, ...$params)
    {
        $method = self::getReflectionMethod($object, $method);

        if ($isString = is_string($object) || $method->isStatic()) {
            if (($declaringClass = $method->getDeclaringClass()->getName()) !== self::toClassName($object)) {
                return self::getReflectionMethod($declaringClass, $method->getName())->invokeArgs(null, $params);
            }

            return $method->invokeArgs(null, $params);
        }

        return $method->invokeArgs($object, $params);
    }
}
