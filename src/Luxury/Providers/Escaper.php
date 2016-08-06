<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Escaper
 *
 * @package Luxury\Bootstrap\Services
 */
class Escaper implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::ESCAPER, \Phalcon\Escaper::class);
    }
}
