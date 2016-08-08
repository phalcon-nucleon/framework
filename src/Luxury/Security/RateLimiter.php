<?php

namespace Luxury\Security;

use Phalcon\Mvc\User\Plugin;

/**
 * Class RateLimiter
 *
 * @see     https://github.com/laravel/framework/blob/5.2/src/Illuminate/Cache/RateLimiter.php
 *
 * @package Luxury\Security
 *
 * @property-read \Phalcon\Cache\BackendInterface cache
 */
class RateLimiter extends Plugin
{
    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string $key
     * @param  int    $maxAttempts
     * @param  int    $decayMinutes
     *
     * @return bool
     */
    public function tooManyAttempts($key, $maxAttempts, $decayMinutes = 1)
    {
        if ($this->cache->exists($key . ':lockout')) {
            return true;
        }
        if ($this->attempts($key) > $maxAttempts) {
            $this->cache->save($key . ':lockout', time() + ($decayMinutes * 60), $decayMinutes);
            $this->resetAttempts($key);

            return true;
        }

        return false;
    }

    /**
     * Increment the counter for a given key for a given decay time.
     *
     * @param  string $key
     * @param  int    $decayMinutes
     *
     * @return int
     */
    public function hit($key, $decayMinutes = 1)
    {
        if (!$this->cache->exists($key, $decayMinutes)) {
            $this->cache->save($key, 1, $decayMinutes);
        }
        $value = (int)$this->cache->get($key, $decayMinutes);

        $value++;
        $this->cache->save($key, $value, $decayMinutes);

        return $value;
    }

    /**
     * Get the number of attempts for the given key.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function attempts($key)
    {
        return is_null($value = $this->cache->get($key)) ? 0 : $value;
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
        return $this->cache->delete($key);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * @param  string $key
     * @param  int    $maxAttempts
     *
     * @return int
     */
    public function retriesLeft($key, $maxAttempts)
    {
        $attempts = $this->attempts($key);

        return $attempts === 0 ? $maxAttempts : $maxAttempts - $attempts + 1;
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
        $this->cache->delete($key . ':lockout');
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * @param  string $key
     *
     * @return int
     */
    public function availableIn($key)
    {
        return $this->cache->get($key . ':lockout') - time();
    }
}