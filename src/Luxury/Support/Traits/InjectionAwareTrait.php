<?php

namespace Luxury\Support\Traits;

use Phalcon\Di;
use Phalcon\DiInterface;

/**
 * Trait InjectionAwareTrait
 *
 * @package Luxury\Support\Traits
 *
 * @property-read \Luxury\Cache\CacheStrategy            $cache
 * @property-read \Phalcon\Mvc\Application               $app
 * @property-read \Phalcon\Config|\stdClass|\ArrayAccess $config
 */
trait InjectionAwareTrait
{
    /**
     * The Services Container
     *
     * @var Di
     */
    protected $_dependencyInjector;

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return DiInterface
     */
    public function getDI()
    {
        if (!isset($this->_dependencyInjector)) {
            $this->setDI(Di::getDefault());
        }

        return $this->_dependencyInjector;
    }

    /**
     * Magic method __get allows to access services in the services container by just
     * only accessing a public property with the same name of a registered service
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
