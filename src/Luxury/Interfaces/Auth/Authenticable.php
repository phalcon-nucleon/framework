<?php

namespace Luxury\Interfaces\Auth;

/**
 * Interface Authenticable
 *
 * @package Luxury\Auth
 */
interface Authenticable
{
    /**
     * Get the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifier() : string;

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword() : string;

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken() : string;

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string $value
     *
     * @return void
     */
    public function setRememberToken(string $value);

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public static function getAuthIdentifierName() : string;

    /**
     * Get the name of the password for the user.
     *
     * @return string
     */
    public static function getAuthPasswordName() : string;

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public static function getRememberTokenName() : string;
}
