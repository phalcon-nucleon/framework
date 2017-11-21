<?php

namespace Neutrino\Database\Migrations\Prefix;

/**
 * Class TimestampPrefix
 *
 * @package Neutrino\Database\Migrations\Prefix
 */
class TimestampPrefix implements PrefixInterface
{
    /**
     * @return string
     */
    public function getPrefix()
    {
        return (string)time();
    }

    /**
     * Remove a prefix from a given str
     *
     * @param string $str
     * @param string $delimiter
     *
     * @return mixed
     */
    public function deletePrefix($str, $delimiter = '_')
    {
        return implode($delimiter, array_slice(explode($delimiter, $str), 1));
    }
}
