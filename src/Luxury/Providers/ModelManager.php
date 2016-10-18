<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;

use Phalcon\Mvc\Model\Manager;

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
     * @return \Phalcon\Mvc\Model\Manager
     */
    protected function register()
    {
        return new Manager;
    }
}
