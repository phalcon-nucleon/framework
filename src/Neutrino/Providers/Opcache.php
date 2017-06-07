<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Opcache\Manager as OpcacheManager;
use Neutrino\Support\SimpleProvider;

/**
 * Class Auth
 *
 *  @package Neutrino\Providers
 */
class Opcache extends SimpleProvider
{
    protected $class = OpcacheManager::class;

    protected $name = Services::OPCACHE;

    protected $shared = true;

    protected $aliases = [OpcacheManager::class];
}
