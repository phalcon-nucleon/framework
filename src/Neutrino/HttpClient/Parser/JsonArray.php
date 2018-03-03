<?php

namespace Neutrino\HttpClient\Parser;

use Neutrino\HttpClient\Contract\Parser\Parserize;

/**
 * Class JsonArray
 *
 * @package Neutrino\HttpClient\Parser
 */
class JsonArray implements Parserize
{
    /**
     * @param $raw
     *
     * @return mixed
     */
    public function parse($raw)
    {
        return json_decode($raw, true);
    }
}
