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
    protected string $class = Memory::class;

    protected string $name = Services::MODELS_METADATA;

    protected bool $shared = true;

    protected array $aliases = [Memory::class];
}
