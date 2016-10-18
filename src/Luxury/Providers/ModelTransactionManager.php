<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;

use Phalcon\Mvc\Model\Transaction\Manager;

/**
 * Class Model
 *
 * @package Luxury\Providers
 */
class ModelTransactionManager extends Provider
{
    protected $name = Services::TRANSACTION_MANAGER;

    protected $shared = true;

    /**
     * @return \Phalcon\Mvc\Model\Transaction\Manager
     */
    protected function register()
    {
        return new Manager;
    }
}
