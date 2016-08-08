<?php

namespace Stub;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\Cache\Backend\File;
use Phalcon\Cache\Frontend\Data;
use Phalcon\DiInterface;

/**
 * Class StubCacheProvider
 *
 * @package     Stub
 */
class StubCacheFileProvider implements Providable
{

    /**
     * @param \Phalcon\DiInterface $di
     *
     * @return void
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::CACHE, function () {
            return new File(new Data(['lifetime' => 3600]), ['cacheDir' => __DIR__ . '/../.data/']);
        });
    }
}
