<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\SimpleProvider;
use Phalcon\Mvc\Model\Transaction\Manager;

/**
 * The `transactionManager` service.
 */
class ModelTransactionManager extends SimpleProvider
{
    protected string $class = Manager::class;

    protected string $name = Services::TRANSACTION_MANAGER;

    protected bool $shared = true;

    protected array $aliases = [Manager::class];
}
