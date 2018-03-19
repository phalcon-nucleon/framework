<?php

namespace Neutrino\HttpClient\Contract\Parser;

/**
 * Interface Parserize
 *
 * @package Neutrino\HttpClient\Contract\Parser
 */
interface Parserize
{
    /**
     * @param string $raw
     *
     * @return mixed
     */
    public function parse($raw);
}
