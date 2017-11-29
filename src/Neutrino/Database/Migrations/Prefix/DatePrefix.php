<?php

namespace Neutrino\Database\Migrations\Prefix;

/**
 * Class DatePrefix
 *
 * @package Neutrino\Database\Migrations\Prefix
 */
class DatePrefix implements PrefixInterface
{
    /**
     * @return string
     */
    public function getPrefix()
    {
        return date('Y_m_d_His');
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
        return implode($delimiter, array_slice(explode($delimiter, $str), 4));
    }
}
