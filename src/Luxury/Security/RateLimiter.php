<?php

namespace Luxury\Security;

use Luxury\Constants\Services;
use Phalcon\Di\Injectable;

/**
 * Class RateLimiter
 *
 * @see     https://github.com/laravel/framework/blob/5.2/src/Illuminate/Cache/RateLimiter.php
 *
 * @package Luxury\Security
 */
class RateLimiter extends Injectable
{
    /**
     * Cache key prefix. The name of the rate limiter.
     *
     * @var string
     */
    private $name;

    /**
     * Cache key suffix for the flag "too many attempts"
     *
     * @var string
     */
    private $klock = '.lockout';

    /**
     * RateLimiter constructor.
     *
     * @param string $name
     */
    public function __construct($name = '')
    {
        $this->name = $name;
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string $key
     * @param  int    $maxAttempts
     * @param  int    $decaySeconds
     *
     * @return bool
     */
    public function tooManyAttempts($key, $maxAttempts, $decaySeconds = 1)
    {
        /** @var \Luxury\Cache\CacheStrategy $cache */
        $cache = $this->{Services::CACHE};

        if ($cache->exists($this->name . $key . $this->klock, $decaySeconds)) {
            return true;
        }
        if ($this->attempts($key, $decaySeconds) >= $maxAttempts) {
            $cache->save(
                $this->name . $key . $this->klock,
                time() + ($decaySeconds),
                $decaySeconds
            );

            $this->resetAttempts($key);

            return true;
        }

        return false;
    }

    /**
     * Increment the counter for a given key for a given decay time.
     *
     * @param  string $key
     * @param  int    $decaySeconds
     *
     * @return int
     */
    public function hit($key, $decaySeconds = 1)
    {
        $key = $this->name . $key;

        /** @var \Luxury\Cache\CacheStrategy $cache */
        $cache = $this->{Services::CACHE};

        if (!$cache->exists($key, $decaySeconds)) {
            $cache->save($key, 0, $decaySeconds);
        }

        $value = (int)$cache->get($key, $decaySeconds);

        $value++;
        $cache->save($key, $value, $decaySeconds);

        return $value;
    }

    /**
     * Get the number of attempts for the given key.
     *
     * @param  string $key
     * @param  int    $decaySeconds
     *
     * @return mixed
     */
    public function attempts($key, $decaySeconds = 1)
    {
        $value = $this->{Services::CACHE}->get($this->name . $key, $decaySeconds);

        return is_null($value) ? 0 : (int)$value;
    }

    /**
     * Reset the number of attempts for the given key.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function resetAttempts($key)
    {
        return $this->{Services::CACHE}->delete($this->name . $key);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * @param  string $key
     * @param  int    $maxAttempts
     * @param         $decaySeconds
     *
     * @return int
     */
    public function retriesLeft($key, $maxAttempts, $decaySeconds)
    {
        $attempts = $this->attempts($key, $decaySeconds);

        return $attempts === 0 ? $maxAttempts : $maxAttempts - $attempts;
    }

    /**
     * Clear the hits and lockout for the given key.
     *
     * @param  string $key
     *
     * @return void
     */
    public function clear($key)
    {
        $this->resetAttempts($key);

        $this->{Services::CACHE}->delete($this->name . $key . $this->klock);
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * @param  string $key
     * @param  int    $decaySeconds
     *
     * @return int
     */
    public function availableIn($key, $decaySeconds)
    {
        $time = $this->{Services::CACHE}
            ->get($this->name . $key . $this->klock, $decaySeconds);

        return $time - time();
    }
}
