<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Model
 *
 * @package Luxury\Bootstrap\Services
 */
class Models implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(
            Services::MODELS_MANAGER,
            \Phalcon\Mvc\Model\Manager::class
        );
        $di->setShared(
            Services::MODELS_METADATA,
            \Phalcon\Mvc\Model\Metadata\Memory::class
        );
        $di->setShared(
            Services::TRANSACTION_MANAGER,
            \Phalcon\Mvc\Model\Transaction\Manager::class
        );
    }
}
