<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

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
     * @param \Phalcon\DiInterface $di
     */
    protected function register(DiInterface $di)
    {
        return new \Phalcon\Mvc\Model\Metadata\Memory;
    }
}
