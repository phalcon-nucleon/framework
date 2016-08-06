<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Crypt
 *
 * @package Luxury\Bootstrap\Services
 */
class Crypt implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::CRYPT, \Phalcon\Crypt::class);
    }
}
