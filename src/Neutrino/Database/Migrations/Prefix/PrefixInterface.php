<?php

namespace Neutrino\Database\Migrations\Prefix;

/**
 * Interface PrefixInterface
 *
 * @package Neutrino\Database\Migrations\Prefix
 */
interface PrefixInterface
{
    /**
     * Return a prefix to append
     *
     * @return string
     */
    public function getPrefix();

    /**
     * Remove a prefix from a given str
     *
     * @param string $str
     * @param string $delimiter
     *
     * @return mixed
     */
    public function deletePrefix($str, $delimiter = '_');
}
