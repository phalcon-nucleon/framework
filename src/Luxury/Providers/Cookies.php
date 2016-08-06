<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Cookies
 *
 * @package Luxury\Bootstrap\Services
 */
class Cookies implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::COOKIES, \Phalcon\Http\Response\Cookies::class);
    }
}
