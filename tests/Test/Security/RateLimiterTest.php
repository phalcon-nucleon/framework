<?php

namespace Test\Security;

use Luxury\Cache\CacheStrategy;
use Luxury\Constants\Services;
use Luxury\Security\RateLimiter;
use Test\TestCase\TestCase;

/**
 * Class RateLimiterTest
 *
 * @package Test\Security
 */
class RateLimiterTest extends TestCase
{
    /**
     * @return array
     */
    public function dataAttemps()
    {
        return [
            [null, 0],
            [1, 1],
        ];
    }

    /**
     * @dataProvider dataAttemps
     *
     * @param $cacheValue
     * @param $excepted
     */
    public function testAttemps($cacheValue, $excepted)
    {
        $mock = $this->mockService(Services::CACHE, CacheStrategy::class, true);

        $mock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($cacheValue));

        $rateLimiter = new RateLimiter('testing');

        $this->assertEquals($excepted, $rateLimiter->attempts('', 1));
    }

    /**
     * @return array
     */
    public function dataAvailableIn()
    {
        return [
            [1, 1],
            [50, 50],
        ];
    }

    /**
     * @dataProvider dataAvailableIn
     *
     * @param $remaining
     * @param $excepted
     */
    public function testAvailableIn($remaining, $excepted)
    {
        $mock = $this->mockService(Services::CACHE, CacheStrategy::class, true);

        $mock->expects($this->any())
            ->method('get')
            ->will($this->returnValue(time() + $remaining));

        $rateLimiter = new RateLimiter('testing');

        $this->assertEquals($excepted, $rateLimiter->availableIn('', 1));
    }
}
