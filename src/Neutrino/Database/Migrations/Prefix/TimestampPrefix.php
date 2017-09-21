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
        return time();
    }
}
