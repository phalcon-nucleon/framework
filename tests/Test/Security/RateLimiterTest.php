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

    /**
     * @return array
     */
    public function dataRetriesLeft()
    {
        return [
            [1, 10, 9],
            [10, 10, 0],
            [5, 10, 5],
            [0, 10, 10],
        ];
    }

    /**
     * @dataProvider dataRetriesLeft
     *
     * @param $cache
     * @param $max
     * @param $excepted
     */
    public function testRetriesLeft($cache, $max, $excepted)
    {
        $mock = $this->mockService(Services::CACHE, CacheStrategy::class, true);

        $mock->expects($this->any())
            ->method('get')
            ->willReturn($cache);

        $rateLimiter = new RateLimiter('testing');

        $this->assertEquals($excepted, $rateLimiter->retriesLeft('', $max, 1));
    }

    public function testHit()
    {
        $mock = $this->mockService(Services::CACHE, CacheStrategy::class, true);

        $mock->expects($this->any())
            ->method('exists')
            ->will($this->returnValue(false));

        $mock->expects($this->any())
            ->method('save')
            ->withConsecutive(
                ['testing', 0],
                ['testing', 1]
            );

        $mock->expects($this->any())
            ->method('get')
            ->willReturn(0);

        $rateLimiter = new RateLimiter('testing');

        $this->assertEquals(1, $rateLimiter->hit('', 1));
    }

    public function testTooManyAttempts()
    {
        $mock = $this->mockService(Services::CACHE, CacheStrategy::class, true);

        $mock->expects($this->any())
            ->method('exists')
            ->will($this->onConsecutiveCalls(true, false, false));

        $mock->expects($this->any())
            ->method('get')
            ->will($this->onConsecutiveCalls(0, 10));

        $mock->expects($this->once())
            ->method('save');

        $mock->expects($this->once())
            ->method('delete');

        $rateLimiter = new RateLimiter('testing');

        $this->assertEquals(true, $rateLimiter->tooManyAttempts('', 10, 1));

        $this->assertEquals(false, $rateLimiter->tooManyAttempts('', 10, 1));

        $this->assertEquals(true, $rateLimiter->tooManyAttempts('', 10, 1));
    }
}
