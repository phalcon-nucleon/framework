<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;

/**
 * Class Crypt
 *
 * @package Luxury\Bootstrap\Services
 */
class Crypt extends Provider
{
    protected $name = Services::CRYPT;

    protected $shared = true;

    /**
     * @return \Phalcon\Crypt
     */
    protected function register()
    {
        return new \Phalcon\Crypt;
    }
}
