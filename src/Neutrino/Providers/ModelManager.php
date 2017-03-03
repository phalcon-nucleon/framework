<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Phalcon\Mvc\Model\Manager;

/**
 * Class ModelManager
 *
 *  @package Neutrino\Providers
 */
class ModelManager extends Provider
{
    protected $name = Services::MODELS_MANAGER;

    protected $shared = true;

    protected $aliases = [Manager::class];

    /**
     * @return \Phalcon\Mvc\Model\Manager
     */
    protected function register()
    {
        return new Manager;
    }
}
