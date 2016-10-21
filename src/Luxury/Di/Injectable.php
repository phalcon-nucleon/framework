<?php

namespace Luxury\Di;

/**
 * Class Injectable
 *
 * @package Luxury\Di
 *
 * @property-read \Phalcon\Cache\BackendInterface        $cache
 * @property-read \Phalcon\Mvc\Application               $app
 * @property-read \Phalcon\Config|\stdClass|\ArrayAccess $config
 */
abstract class Injectable extends \Phalcon\Di\Injectable
{
    /**
     * Injectable constructor.
     *
     * Apply Di::getDefault() into injectable.
     */
    public function __construct()
    {
    }

    /**
     * Override Magic method __get
     *  \_ break the default forced cache value of registered component founded on the Di.
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
