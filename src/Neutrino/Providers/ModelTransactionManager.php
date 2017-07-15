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
    protected $class = Manager::class;

    protected $name = Services::TRANSACTION_MANAGER;

    protected $shared = true;

    protected $aliases = [Manager::class];
}
