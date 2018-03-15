<?php

namespace Test\Parser;

use Neutrino\HttpClient\Parser\Json;
use PHPUnit\Framework\TestCase;

/**
 * Class Json
 *
 * @package     Test\Parser
 */
class JsonTest extends TestCase
{
    public function testParse()
    {
        $parser = new Json();

        $this->assertEquals((object)['data' => 'test'], $parser->parse(json_encode(['data' => 'test'])));
    }
}
