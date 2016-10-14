<?php

namespace Luxury\Support\Traits;

use Phalcon\Di;

/**
 * Class InjectionAwarable
 *
 * @package Luxury\Support\Traits
 */
trait InjectionAwareTrait
{
    protected $_dependencyInjector;

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        if (!isset($this->_dependencyInjector)) {
            $this->setDI(Di::getDefault());
        }

        return $this->_dependencyInjector;
    }

    /**
     * Magic method __get
     *
     * @param string $propertyName
     *
     * @return mixed|null
     */
    public function __get($propertyName)
    {
        if (($di = $this->getDI()) && $di->has($propertyName)) {
            return $di->get($propertyName);
        }

        return null;
    }
}
