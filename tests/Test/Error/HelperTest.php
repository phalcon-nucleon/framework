<?php

namespace Test\Error;

use Neutrino\Error\Helper;
use Phalcon\Logger as Phogger;
use Test\TestCase\TestCase;

class HelperTest extends TestCase
{
    public function dataVerboseType()
    {
        return [
            ['null', null],
            ['Array', []],
            ['123', 123],
            ['123.123', 123.123],
            ["'str'", 'str'],
            ['stdClass', new \stdClass()],
            ['stdClass[]', [new \stdClass()]],
            ['stdClass[]', [new \stdClass(), new \stdClass(), new \stdClass(), new \stdClass()]],
            ['Array', [new \stdClass(), null, new \stdClass(), 123]],
        ];
    }

    /**
     * @dataProvider dataVerboseType
     */
    public function testVerboseType($expected, $value)
    {
        $this->assertEquals($expected, Helper::verboseType($value));
    }

    /**
     * @dataProvider dataVerboseType
     */
    public function testVerboseTypeResource()
    {
        $this->assertEquals('resource', Helper::verboseType($f = fopen('php://memory', 'r')));
        fclose($f);
    }
    /**
     * @dataProvider dataVerboseType
     */
    public function testVerboseTypeUnknown()
    {
        $f = fopen('php://memory', 'r');
        fclose($f);
        $this->assertEquals('?', Helper::verboseType($f));
    }

    public function dataGetErrorType()
    {
        return [
            ['Uncaught exception', -1],
            ['E_ERROR', E_ERROR],
            ['E_WARNING', E_WARNING],
            ['E_PARSE', E_PARSE],
            ['E_NOTICE', E_NOTICE],
            ['E_CORE_ERROR', E_CORE_ERROR],
            ['E_CORE_WARNING', E_CORE_WARNING],
            ['E_COMPILE_ERROR', E_COMPILE_ERROR],
            ['E_COMPILE_WARNING', E_COMPILE_WARNING],
            ['E_USER_ERROR', E_USER_ERROR],
            ['E_USER_WARNING', E_USER_WARNING],
            ['E_USER_NOTICE', E_USER_NOTICE],
            ['E_STRICT', E_STRICT],
            ['E_RECOVERABLE_ERROR', E_RECOVERABLE_ERROR],
            ['E_DEPRECATED', E_DEPRECATED],
            ['E_USER_DEPRECATED', E_USER_DEPRECATED],
            ['Other', 'Other'],
        ];
    }

    /**
     * @dataProvider dataGetErrorType
     */
    public function testGetErrorType($expected, $value)
    {
        $this->assertEquals($expected, Helper::getErrorType($value));
    }

    public function dataGetLogType()
    {
        return [
            [Phogger::CRITICAL, E_PARSE],
            [Phogger::EMERGENCY, E_COMPILE_ERROR],
            [Phogger::EMERGENCY, E_CORE_ERROR],
            [Phogger::EMERGENCY, E_ERROR],
            [Phogger::ERROR, -1],
            [Phogger::ERROR, E_RECOVERABLE_ERROR],
            [Phogger::ERROR, E_USER_ERROR],
            [Phogger::WARNING, E_WARNING],
            [Phogger::WARNING, E_USER_WARNING],
            [Phogger::WARNING, E_CORE_WARNING],
            [Phogger::WARNING, E_COMPILE_WARNING],
            [Phogger::NOTICE, E_NOTICE],
            [Phogger::NOTICE, E_USER_NOTICE],
            [Phogger::INFO, E_STRICT],
            [Phogger::INFO, E_DEPRECATED],
            [Phogger::INFO, E_USER_DEPRECATED],
        ];
    }

    /**
     * @dataProvider dataGetLogType
     */
    public function testGetLogType($expected, $value)
    {
        $this->assertEquals($expected, Helper::getLogType($value));
    }
}