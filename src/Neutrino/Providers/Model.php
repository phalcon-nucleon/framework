<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Neutrino\Interfaces\Providable;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;
use Phalcon\Mvc\Model\Manager as ModelManager;
use Phalcon\Mvc\Model\Metadata\Memory as ModelMetadataMemory;
use Phalcon\Mvc\Model\Transaction\Manager as ModelTransactionManager;

/**
 * Class Model
 *
 *  @package Neutrino\Providers
 */
class Model extends Injectable  implements Providable
{
    /**
     * @inheritdoc
     */
    public function registering()
    {
        $di = $this->getDI();

        $modelManagerService = new Service(Services::MODELS_MANAGER, ModelManager::class, true);
        $di->setRaw(Services::MODELS_MANAGER, $modelManagerService);
        $di->setRaw(ModelManager::class, $modelManagerService);

        $modelMetadataService = new Service(Services::MODELS_METADATA, ModelMetadataMemory::class, true);
        $di->setRaw(Services::MODELS_METADATA, $modelMetadataService);
        $di->setRaw(ModelMetadataMemory::class, $modelMetadataService);

        $transactionManagerService = new Service(Services::TRANSACTION_MANAGER, ModelTransactionManager::class, true);
        $di->setRaw(Services::TRANSACTION_MANAGER, $transactionManagerService);
        $di->setRaw(ModelTransactionManager::class, $transactionManagerService);
    }
}
