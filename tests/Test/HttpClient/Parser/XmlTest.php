<?php

namespace Test\Parser;

use Neutrino\HttpClient\Parser\Xml;
use PHPUnit\Framework\TestCase;

/**
 * Class Xml
 *
 * @package     Test\Parser
 */
class XmlTest extends TestCase
{
    public function testParse()
    {
        $parser = new Xml();

        $xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
<test>value</test>
<test>value</test>
</root>
XML;
        $xml       = simplexml_load_string($xmlString);

        $this->assertEquals($xml, $parser->parse($xmlString));
        $this->assertEquals(var_export($xml, true), var_export($parser->parse($xmlString), true));
    }
}
