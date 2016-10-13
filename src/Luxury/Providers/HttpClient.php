<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Phalcon\DiInterface;

/**
 * Class HttpClient
 *
 * @package     Luxury\Providers
 */
class HttpClient extends Provider
{
    protected $name = Services::HTTP_CLIENT;

    protected $shared = true;

    /**
     * @param \Phalcon\DiInterface $di
     *
     * @return \Luxury\Http\Client
     */
    protected function register(DiInterface $di)
    {
        return new \Luxury\Http\Client;
    }
}
