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
}
