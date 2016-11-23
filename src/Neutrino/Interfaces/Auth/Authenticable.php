<?php

namespace Neutrino\Interfaces\Auth;

/**
 * Interface Authenticable
 *
 *  @package Neutrino\Auth
 */
interface Authenticable
{
    /**
     * Get the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifier();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword();

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken();

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string $value
     *
     * @return void
     */
    public function setRememberToken($value);

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public static function getAuthIdentifierName();

    /**
     * Get the name of the password for the user.
     *
     * @return string
     */
    public static function getAuthPasswordName();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public static function getRememberTokenName();
}
