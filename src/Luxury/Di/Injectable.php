<?php

namespace Luxury\Di;

/**
 * Class Injectable
 * @package Luxury\Di
 *
 * @property-read \Phalcon\Cache\BackendInterface cache
 * @property-read \Phalcon\Mvc\Application        app
 */
abstract class Injectable extends \Phalcon\Di\Injectable
{
    public function __construct()
    {
        // Apply Di::getDefault() into injectable.
        $this->setDI($this->getDI());
    }
}
