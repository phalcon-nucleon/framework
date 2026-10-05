<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Neutrino\Support\SimpleProvider;
use Phalcon\Mvc\Model\Manager;

/**
 * Class ModelManager
 *
 *  @package Neutrino\Providers
 */
class ModelManager extends SimpleProvider
{
    protected string $class = Manager::class;

    protected string $name = Services::MODELS_MANAGER;

    protected bool $shared = true;

    protected array $aliases = [Manager::class];
}
