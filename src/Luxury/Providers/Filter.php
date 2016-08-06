<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Filter
 *
 * @package Luxury\Bootstrap\Services
 */
class Filter implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::FILTER, \Phalcon\Filter::class);
    }
}
