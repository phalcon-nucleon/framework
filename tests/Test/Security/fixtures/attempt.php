<?php

declare(strict_types=1);

/*
 * One throttled attempt on Redis, from a separate process (RateLimiterTest::testConcurrentAttemptsOnRedis).
 * Prints 1 when the attempt is accepted, 0 when it is refused.
 *
 *   php attempt.php <redis host> <key prefix> <max attempts> <start file>
 */

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers\Cache;
use Neutrino\Security\RateLimiter;
use Phalcon\Di\Di;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

[, $host, $prefix, $max, $start] = $argv;

$di = new Di();
Di::setDefault($di);
$di->setShared(Services::CONFIG, new Config(['cache' => ['default' => 'redis', 'stores' => [
    'redis' => ['adapter' => 'redis', 'options' => ['host' => $host, 'prefix' => $prefix]],
]]]));
ProviderRegistrar::register($di, [Cache::class]);

$limiter = new RateLimiter('concurrent');
$limiter->attempts('key'); // connected and warm before the start

// All the processes attempt at the same time.
while (!file_exists($start)) {
    usleep(50);
}

echo $limiter->attempt('key', (int) $max, 60) === null ? '0' : '1';
