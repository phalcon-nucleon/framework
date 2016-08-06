<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class ModelManager
 *
 * @package Luxury\Bootstrap\Services
 */
class ModelManager implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::MODELS_MANAGER, \Phalcon\Mvc\Model\Manager::class);
    }
}
