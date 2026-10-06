<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Interfaces\Providable;
use Phalcon\Di\Injectable;

/**
 * The `modelsManager`, `modelsMetadata` and `transactionManager` services.
 */
class Model extends Injectable implements Providable
{
    public function registering(): void
    {
        ProviderRegistrar::register($this->getDI(), [ModelManager::class, ModelsMetaData::class, ModelTransactionManager::class]);
    }
}
