<?php

namespace Luxury\Support\Facades;

use Luxury\Constants\Services;

/**
 * Class HttpClient
 *
 * @package     Luxury\Support\Facades
 *
 * @method static \Phalcon\Http\Client\Response get() get(string $url, array $params = [], array $header = [], callable|bool $autoRedirect = null)
 * @method static \Phalcon\Http\Client\Response delete() delete(string $url, array $params = [], array $header = [], callable|bool $autoRedirect = null)
 * @method static \Phalcon\Http\Client\Response patch() patch(string $url, array $params = [], array $header = [], callable|bool $autoRedirect = null)
 * @method static \Phalcon\Http\Client\Response post() post(string $url, array $params = [], array $header = [], callable|bool $autoRedirect = null)
 * @method static \Phalcon\Http\Client\Response put() put(string $url, array $params = [], array $header = [], callable|bool $autoRedirect = null)
 */
class HttpClient extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Services::HTTP_CLIENT;
    }
}
