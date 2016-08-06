<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class HttpClient
 *
 * @package     Luxury\Providers
 */
class HttpClient implements Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::HTTP_CLIENT, \Luxury\Http\Client::class);
    }
}
