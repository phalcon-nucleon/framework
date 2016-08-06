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
class ModelsMetaData implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::MODELS_METADATA, \Phalcon\Mvc\Model\Metadata\Memory::class);
    }
}
