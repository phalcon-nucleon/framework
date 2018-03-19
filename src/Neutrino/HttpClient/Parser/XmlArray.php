<?php

namespace Neutrino\HttpClient\Parser;

/**
 * Class XmlArray
 *
 * @package Neutrino\HttpClient\Parser
 */
class XmlArray extends Xml
{
    /**
     * @param $raw
     *
     * @return mixed
     */
    public function parse($raw)
    {
        return json_decode(json_encode(parent::parse($raw)), true);
    }
}
