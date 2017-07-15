<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Neutrino\Support\SimpleProvider;
use Phalcon\Mvc\Model\Metadata\Memory;

/**
 * Class ModelsMetaData
 *
 *  @package Neutrino\Foundation\Bootstrap
 */
class ModelsMetaData extends SimpleProvider
{
    protected $class = Memory::class;

    protected $name = Services::MODELS_METADATA;

    protected $shared = true;

    protected $aliases = [Memory::class];
}
