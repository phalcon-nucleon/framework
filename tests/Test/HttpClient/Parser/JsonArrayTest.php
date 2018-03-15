<?php

namespace Test\Parser;

use Neutrino\HttpClient\Parser\Json;
use Neutrino\HttpClient\Parser\JsonArray;
use PHPUnit\Framework\TestCase;

/**
 * Class Json
 *
 * @package     Test\Parser
 */
class JsonArrayTest extends TestCase
{
    public function testParse()
    {
        $parser = new JsonArray();

        $this->assertEquals(['data' => 'test'], $parser->parse(json_encode(['data' => 'test'])));
    }
}
