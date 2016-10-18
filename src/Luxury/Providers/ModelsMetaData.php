<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;

use Phalcon\Mvc\Model\Metadata\Memory;

/**
 * Class ModelsMetaData
 *
 * @package Luxury\Foundation\Bootstrap
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
