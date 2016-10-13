<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class ModelManager
 *
 * @package Luxury\Bootstrap\Services
 */
class ModelManager extends Provider
{
    protected $name = Services::MODELS_MANAGER;

    protected $shared = true;

    /**
     * @param \Phalcon\DiInterface $di
     */
    protected function register(DiInterface $di)
    {
        return new \Phalcon\Mvc\Model\Manager;
    }
}
