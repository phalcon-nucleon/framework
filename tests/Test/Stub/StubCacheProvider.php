<?php

namespace Test\Stub;

use Neutrino\Constants\Services;
use Neutrino\Providers\Provider;

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
