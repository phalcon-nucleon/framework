<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Http\Client;


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
     * @return \Luxury\Http\Client
     */
    protected function register()
    {
        return new Client;
    }
}
