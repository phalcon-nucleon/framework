<?php

namespace Test\Stub;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Luxury\Providers\Provider;
use Phalcon\DiInterface;

/**
 * Class StubCacheProvider
 *
 * @package     Stub
 */
class StubCacheProvider extends Provider
{

    protected $name = Services::CACHE;

    protected $shared = true;

    public function register()
    {
        return new StubCache();
    }
}
