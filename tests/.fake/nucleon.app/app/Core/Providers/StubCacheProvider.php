<?php

namespace Fake\Core\Providers;

use Fake\Core\Cache\StubCache;
use Neutrino\Constants\Services;
use Neutrino\Support\Provider;

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
