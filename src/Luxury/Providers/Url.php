<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Phalcon\DiInterface;

/**
 * Class Url
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Url extends Provider
{
    protected $name = Services::URL;

    protected $shared = true;

    /**
     * The URL component is used to generate all kind of urls in the application
     *
     * @param \Phalcon\DiInterface $di
     *
     * @return \Phalcon\Mvc\Url
     */
    protected function register(DiInterface $di)
    {
        $url = new \Phalcon\Mvc\Url();

        $url->setBaseUri($di->getShared(Services::CONFIG)->application->baseUri);

        return $url;
    }
}
