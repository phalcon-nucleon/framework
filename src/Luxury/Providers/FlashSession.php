<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Phalcon\DiInterface;

/**
 * Class FlashSession
 *
 * @package Luxury\Providers
 */
class FlashSession extends Provider
{
    protected $name = Services::FLASH_SESSION;

    protected $shared = true;

    /**
     * @param \Phalcon\DiInterface $di
     *
     * @return mixed
     */
    protected function register(DiInterface $di)
    {
        return new \Phalcon\Flash\Session;
    }
}
