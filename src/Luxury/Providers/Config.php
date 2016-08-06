<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Config
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Config implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::CONFIG, function () {
            return new \Phalcon\Config\Adapter\Php(APP_PATH . '/config/config.php');
        });
    }
}
