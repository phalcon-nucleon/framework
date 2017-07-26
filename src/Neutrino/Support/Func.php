<?php

namespace Neutrino\Support;

/**
 * Class Helper
 *
 * @package Neutrino\Support
 */
final class Func
{
    /**
     * @param mixed    $value
     * @param \Closure $callback
     *
     * @return mixed
     */
    public static function tap($value, \Closure $callback)
    {
        $callback($value);

        return $value;
    }
}