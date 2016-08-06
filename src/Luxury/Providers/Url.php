<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Url
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Url implements Providable
{
    /**
     * The URL component is used to generate all kind of urls in the application
     *
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::URL, function () {
            /* @var \Phalcon\Di $this */
            $url = new \Phalcon\Mvc\Url();
            $url->setBaseUri($this->getShared(Services::CONFIG)->application->baseUri);

            return $url;
        });
    }
}
