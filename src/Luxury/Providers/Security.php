<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Security
 *
 * @package Luxury\Bootstrap\Services
 */
class Security implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::SECURITY, \Luxury\Security\SecurityPlugin::class);
    }
}
