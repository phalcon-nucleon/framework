<?php

namespace Neutrino\Support;

/**
 * Class Hacker
 *
 * Allows access to any methods or properties of a class.
 * Should only be used for debugging, or UnitTest.
 *
 * @package Neutrino\Support
 */
final class Reflacker
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
     * @param $class
     *
     * @return \ReflectionClass
     */
    private static function getReflectionClass($class)
    {
        if (!isset(self::$cache[$class]['class'])) {
            self::$cache[$class]['class'] = new \ReflectionClass($class);
        }

        return self::$cache[$class]['class'];
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
     */
    private static function getReflectionElement($object, $type, $name)
    {
        return self::retrieveReflectionElement(self::getReflectionClass(self::toClassName($object)), $type, $name);
    }

    /**
     * @param string|object $object
     * @param string        $property
     *
     * @return \ReflectionProperty
     */
    private static function getReflectionProperty($object, $property)
    {
        return self::getReflectionElement($object, 'property', $property);
    }

    /**
     * @param string|object $object
     * @param string        $method
     *
     * @return \ReflectionMethod
     */
    private static function getReflectionMethod($object, $method)
    {
        return self::getReflectionElement($object, 'method', $method);
    }

    /**
     * @param mixed  $object
     * @param string $property
     * @param mixed  $value
     */
    public static function set($object, $property, $value)
    {
        self::getReflectionProperty($object, $property)->setValue(is_string($object) ? null : $object, $value);
    }

    /**
     * @param string|mixed $object
     * @param string       $property
     *
     * @return mixed
     */
    public static function get($object, $property)
    {
        return self::getReflectionProperty($object, $property)->getValue(is_string($object) ? null : $object);
    }

    /**
     * @param string|mixed $object
     * @param string       $method
     * @param array        ...$params
     *
     * @return mixed
     */
    public static function invoke($object, $method, ...$params)
    {
        return self::getReflectionMethod($object, $method)->invokeArgs(is_string($object) ? null : $object, $params);
    }
}
