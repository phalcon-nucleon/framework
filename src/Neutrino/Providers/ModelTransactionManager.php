<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Neutrino\Support\SimpleProvider;
use Phalcon\Mvc\Model\Transaction\Manager;

/**
 * Class Model
 *
 *  @package Neutrino\Providers
 */
class ModelTransactionManager extends SimpleProvider
{
    protected string $class = Manager::class;

    protected string $name = Services::TRANSACTION_MANAGER;

    protected bool $shared = true;

    protected array $aliases = [Manager::class];
}
