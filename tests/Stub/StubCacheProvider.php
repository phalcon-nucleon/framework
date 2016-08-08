<?php

namespace Stub;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class StubCacheProvider
 *
 * @package     Stub
 */
class StubCacheProvider implements Providable
{

    /**
     * @param \Phalcon\DiInterface $di
     *
     * @return void
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::CACHE, function () {
            return new StubCache();
        });
    }
}
