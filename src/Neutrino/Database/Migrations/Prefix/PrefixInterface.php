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
     * @return string
     */
    public function getPrefix();
}
