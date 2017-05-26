<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Opcache\Manager as OpcacheManager;

/**
 * Class Auth
 *
 *  @package Neutrino\Providers
 */
class Opcache extends BasicProvider
{
    protected $class = OpcacheManager::class;

    protected $name = Services::OPCACHE;

    protected $shared = true;

    protected $aliases = [OpcacheManager::class];
}
