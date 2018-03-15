<?php

namespace Test\Parser;

use Neutrino\HttpClient\Parser\XmlArray;
use PHPUnit\Framework\TestCase;

/**
 * Class Xml
 *
 * @package     Test\Parser
 */
class XmlArrayTest extends TestCase
{
    public function testParse()
    {
        $parser = new XmlArray();

        $xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
<test>value</test>
<test>value</test>
</root>
XML;

        $this->assertEquals(['test' => ['value', 'value']], $parser->parse($xmlString));
    }
}
