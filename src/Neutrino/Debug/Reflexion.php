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
     *
     * @throws \ReflectionException
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
     * @throws \ReflectionException
     */
    private static function getReflectionElement($object, $type, $name)
    {
        return self::retrieveReflectionElement(self::getReflectionClass(self::toClassName($object)), $type, $name);
    }

    /**
     * @param string $class
     *
     * @return \ReflectionClass
     * @throws \ReflectionException
     */
    public static function getReflectionClass($class)
    {
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
     * @throws \ReflectionException
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
     * @throws \ReflectionException
     */
    public static function getReflectionMethod($object, $method)
    {
        return self::getReflectionElement($object, 'method', $method);
    }

    /**
     * @param string|object $object
     *
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    public static function getReflectionProperties($object)
    {
        return self::getReflectionClass(self::toClassName($object))->getProperties();
    }

    /**
     * @param string|object $object
     *
     * @return \ReflectionMethod[]
     * @throws \ReflectionException
     */
    public static function getReflectionMethods($object)
    {
        return self::getReflectionClass(self::toClassName($object))->getMethods();
    }

    /**
     * @param string|object $object
     * @param string $property
     * @param mixed  $value
     *
     * @throws \ReflectionException
     */
    public static function set($object, $property, $value)
    {
        self::getReflectionProperty($object, $property)->setValue(is_string($object) ? null : $object, $value);
    }

    /**
     * @param string|object $object
     * @param string       $property
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public static function get($object, $property)
    {
        return self::getReflectionProperty($object, $property)->getValue(is_string($object) ? null : $object);
    }

    /**
     * @param string|object $object
     * @param string       $method
     * @param array        ...$params
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public static function invoke($object, $method, ...$params)
    {
        return self::getReflectionMethod($object, $method)->invokeArgs(is_string($object) ? null : $object, $params);
    }
}
