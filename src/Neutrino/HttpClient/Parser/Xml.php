<?php

namespace Neutrino\HttpClient\Parser;

use Neutrino\HttpClient\Contract\Parser\Parserize;

/**
 * Class Xml
 *
 * @package Neutrino\HttpClient\Parser
 */
class Xml implements Parserize
{
    /**
     * @param $raw
     *
     * @return \SimpleXMLElement
     */
    public function parse($raw)
    {
        return simplexml_load_string($raw);
    }
}
