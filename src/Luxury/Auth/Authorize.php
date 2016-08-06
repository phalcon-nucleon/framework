<?php

namespace Luxury\Auth;

/**
 * Interface GateInterface
 *
 * @package Luxury\Auth
 */
interface Authorize
{
    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function allows(array $credentials = []);

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function denies(array $credentials = []);

    /**
     * Determine if the given ability should be granted.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function check(array $credentials = []);

    /**
     * Get a guard instance for the given user.
     *
     * @param  \Luxury\Auth\Authenticable $user
     *
     * @return static
     */
    public function forUser(Authenticable $user);
}
