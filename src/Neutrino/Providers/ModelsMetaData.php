<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;

use Phalcon\Mvc\Model\Metadata\Memory;

/**
 * Class ModelsMetaData
 *
 *  @package Neutrino\Foundation\Bootstrap
 */
class ModelsMetaData extends Provider
{
    protected $name = Services::MODELS_METADATA;

    protected $shared = true;

    /**
     * @return Memory
     */
    protected function register()
    {
        return new Memory;
    }
}
