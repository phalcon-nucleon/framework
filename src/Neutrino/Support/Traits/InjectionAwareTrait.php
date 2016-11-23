<?php

namespace Neutrino\Support\Traits;

use Phalcon\Di;
use Phalcon\DiInterface;

/**
 * Trait InjectionAwareTrait
 *
 *  @package Neutrino\Support\Traits
 *
 * @property-read \Neutrino\Cache\CacheStrategy          $cache
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
    protected $_di;

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        $this->_di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return DiInterface
     */
    public function getDI()
    {
        if (!isset($this->_di)) {
            $this->setDI(Di::getDefault());
        }

        return $this->_di;
    }

    public function __get($name)
    {
        if (!isset($this->$name)) {
            $di = $this->getDI();

            if (!$di->has($name)) {
                throw new \RuntimeException("$name not found in dependency injection.");
            }

            $this->$name = $di->getShared($name);
        }

        return $this->$name;
    }
}
