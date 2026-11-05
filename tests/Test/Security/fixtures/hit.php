<?php

declare(strict_types=1);

/*
 * Hits a rate limiter N times on Redis, from a separate process (RateLimiterTest::testConcurrentHitsOnRedis).
 *
 *   php hit.php <redis host> <key prefix> <hits>
 */

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers\Cache;
use Neutrino\Security\RateLimiter;
use Phalcon\Di\Di;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

[, $host, $prefix, $hits] = $argv;

$di = new Di();
Di::setDefault($di);
$di->setShared(Services::CONFIG, new Config(['cache' => ['default' => 'redis', 'stores' => [
    'redis' => ['adapter' => 'redis', 'options' => ['host' => $host, 'prefix' => $prefix]],
]]]));
ProviderRegistrar::register($di, [Cache::class]);

$limiter = new RateLimiter('concurrent');

for ($i = 0; $i < (int) $hits; $i++) {
    $limiter->hit('key', 60);
}
