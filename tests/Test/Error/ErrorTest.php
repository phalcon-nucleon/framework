<?php

namespace Test\Error;

use Neutrino\Error\Error;
use Neutrino\Support\Reflacker;
use Test\TestCase\TestCase;

/**
 * Class ErrorTest
 *
 * @package Test\Error
 */
class ErrorTest extends TestCase
{

    public function dataError()
    {
        return [
            [[]],
        ];
    }

    /**
     * @dataProvider dataError
     */
    public function testConstruct($options)
    {
        $error = new Error($options);

        $attributes = Reflacker::get($error, 'attributes');

        $keys = [
            'type',
            'message',
            'file',
            'line',
            'exception',
            'isException',
            'isError',
        ];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $attributes);
            $this->assertEquals($attributes[$key], $error->$key);
        }
    }
}
