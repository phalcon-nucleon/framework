<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 07/11/2016
 * Time: 10:49
 */

namespace Test\Cli\Output;


use Neutrino\Cli\Output\Decorate;

class DecorateTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Force Enable Decoration for windows
        putenv('TERM=xterm');
    }

    public function tearDown()
    {
        parent::tearDown();

        // Force Enable Decoration for windows
        putenv('TERM=');
    }

    public function dataColorisedFunctions()
    {
        return [
            ["\033[32mtest\033[39m", 'info'],
            ["\033[33mtest\033[39m", 'notice'],
            ["\033[33;7mtest\033[39;27m", 'warn'],
            ["\033[30;41mtest\033[39;49m", 'error'],
            ["\033[30;46mtest\033[39;49m", 'question'],
        ];
    }

    /**
     * @dataProvider dataColorisedFunctions
     */
    public function testColorisedFunctions($expected, $func)
    {
        $this->assertEquals($expected, Decorate::$func('test'));
    }
}
