<?php

namespace Luxury\Support\Facades;

use Luxury\Constants\Services;

/**
 * Class Url
 *
 * @package Luxury\Support\Facades
 *
 * @method static string getBasePath() Returns a base path
 * @method static string get(string|array $uri = null, array|object $args = null, bool $local = null) Generates a URL
 * @method static string path(string $path = null) Generates a local path
 */
class Url extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Services::URL;
    }
}
