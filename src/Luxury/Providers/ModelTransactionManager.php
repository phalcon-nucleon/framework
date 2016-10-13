<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Phalcon\DiInterface;

/**
 * Class Model
 *
 * @package Luxury\Bootstrap\Services
 */
class ModelTransactionManager extends Provider
{
    protected $name = Services::TRANSACTION_MANAGER;

    protected $shared = true;

    /**
     * @param \Phalcon\DiInterface $di
     */
    protected function register(DiInterface $di)
    {
        return new \Phalcon\Mvc\Model\Transaction\Manager;
    }
}
