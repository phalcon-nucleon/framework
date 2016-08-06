<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Logger
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Logger implements Providable
{
    /**
     * Register the logger
     *
     * @param \Phalcon\DiInterface $di
     *
     * @internal param \Luxury\Foundation\Application $app
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::LOGGER, function () {
            /* @var \Phalcon\Di $this */
            return new \Phalcon\Logger\Adapter\File\Multiple($this->getShared(Services::CONFIG)->application->logDir);
        });
    }
}
