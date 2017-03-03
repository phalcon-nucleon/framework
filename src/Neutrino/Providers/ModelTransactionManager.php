<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Phalcon\Mvc\Model\Transaction\Manager;

/**
 * Class Model
 *
 *  @package Neutrino\Providers
 */
class ModelTransactionManager extends Provider
{
    protected $name = Services::TRANSACTION_MANAGER;

    protected $shared = true;

    protected $aliases = [Manager::class];

    /**
     * @return \Phalcon\Mvc\Model\Transaction\Manager
     */
    protected function register()
    {
        return new Manager;
    }
}
