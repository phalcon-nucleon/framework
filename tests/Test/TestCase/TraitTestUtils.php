<?php

namespace Test\TestCase;

/**
 * Trait TraitTestUtils
 *
 * @package     Test\TestCase
 */
trait TraitTestUtils
{
    /**
     * @param string $className
     * @param string $propertyName
     *
     * @return \ReflectionProperty
     */
    public function getPrivateProperty($className, $propertyName)
    {
        $reflector = new \ReflectionClass($className);
        $property  = $reflector->getProperty($propertyName);

        $property->setAccessible(true);

        return $property;
    }

    /**
     * @param string $className
     * @param string $methodName
     *
     * @return \ReflectionMethod
     */
    public function getPrivateMethod($className, $methodName)
    {
        $reflection = new \ReflectionClass($className);
        $method     = $reflection->getMethod($methodName);

        $method->setAccessible(true);

        return $method;
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object      &$object    Instantiated object that we will run method on.
     * @param string      $methodName Method name to call
     * @param array       $parameters Array of parameters to pass into method.
     * @param string|null $className
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [], $className = null)
    {
        return $this->getPrivateMethod(
            !is_null($className) ? $className : get_class($object),
            $methodName
        )->invokeArgs($object, $parameters);
    }

    /**
     * Call protected/private static method of a class.
     *
     * @param string $class
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeStaticMethod($class, $methodName, array $parameters = [])
    {
        return $this->getPrivateMethod(
            $class,
            $methodName
        )->invokeArgs(null, $parameters);
    }

    /**
     * Get value of an private/protected property of an object.
     *
     * @param object $object
     * @param string $propertyName
     * @param null   $className
     *
     * @return mixed
     */
    public function getValueProperty(&$object, $propertyName, $className = null)
    {
        return $this->getPrivateProperty(
            $className ? $className : get_class($object),
            $propertyName
        )->getValue($object);
    }

    /**
     * Get value of an private/protected property of an object.
     *
     * @param string $class
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getStaticValueProperty($class, $propertyName)
    {
        return $this->getPrivateProperty(
            $class,
            $propertyName
        )->getValue(null);
    }

    /**
     * Set value of an private/protected static property of a class.
     *
     * @param object $object
     * @param string $propertyName
     * @param mixed  $value
     * @param null   $className
     */
    public function setValueProperty(&$object, $propertyName, $value, $className = null)
    {
        $this->getPrivateProperty(
            $className ? $className : get_class($object),
            $propertyName
        )->setValue($object, $value);
    }

    /**
     * Set value of an private/protected property of a class.
     *
     * @param string $class
     * @param string $propertyName
     * @param mixed  $value
     */
    public function setStaticValueProperty($class, $propertyName, $value)
    {
        $this->getPrivateProperty(
            $class,
            $propertyName
        )->setValue(null, $value);
    }
}
