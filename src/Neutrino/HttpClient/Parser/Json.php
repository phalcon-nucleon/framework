<?php

namespace Neutrino\HttpClient\Parser;

use Neutrino\HttpClient\Contract\Parser\Parserize;

/**
 * Class Json
 *
 * @package Neutrino\HttpClient\Parser
 */
class Json implements Parserize
{
    /**
     * @param $raw
     *
     * @return mixed
     */
    public function parse($raw)
    {
        return json_decode($raw);
    }
}
