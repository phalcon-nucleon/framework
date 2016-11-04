<?php

namespace Test\Cli\Output;


use Luxury\Cli\Output\ConsoleOutput;
use Luxury\Cli\Output\Helper;
use Luxury\Foundation\Cli\ListTask;
use Test\Stub\StubTask;

class HelperTest extends \PHPUnit_Framework_TestCase
{
    public function dataRemoveDecoration()
    {
        return [
            ['test', 'test'],
            ['test', "\033[32mtest\033[39m"],
            ['test', "\033[30;41mtest\033[39;49m"],
        ];
    }

    /**
     * @dataProvider dataRemoveDecoration
     */
    public function testRemoveDecoration($expected, $str)
    {
        $this->assertEquals($expected, Helper::removeDecoration($str));
    }

    public function dataStrlenWithoutDecoration()
    {
        return [
            [4, 'test'],
            [4, "\033[32mtest\033[39m"],
            [4, "\033[30;41mtest\033[39;49m"],
        ];
    }

    /**
     * @dataProvider dataStrlenWithoutDecoration
     */
    public function testStrlenWithoutDecoration($expected, $str)
    {
        $this->assertEquals($expected, Helper::strlenWithoutDecoration($str));
    }

    public function dataStrlen()
    {
        return [
            [4, 'test'],
            [14, "\033[32mtest\033[39m"],
            [20, "\033[30;41mtest\033[39;49m"],
        ];
    }

    /**
     * @dataProvider dataStrlen
     */
    public function testStrlen($expected, $str)
    {
        $this->assertEquals($expected, Helper::strlen($str));
    }

    public function dataStrPad()
    {
        return [
            ['test    ', 'test', 8, ' ', STR_PAD_RIGHT],
            ["\033[32mtest\033[39m    ", "\033[32mtest\033[39m", 8, ' ', STR_PAD_RIGHT],
            ['    test', 'test', 8, ' ', STR_PAD_LEFT],
            ["    \033[32mtest\033[39m", "\033[32mtest\033[39m", 8, ' ', STR_PAD_LEFT],
            ['  test  ', 'test', 8, ' ', STR_PAD_BOTH],
            ["  \033[32mtest\033[39m  ", "\033[32mtest\033[39m", 8, ' ', STR_PAD_BOTH],
        ];
    }

    /**
     * @dataProvider dataStrPad
     */
    public function testStrPad($expected, $str, $len, $pad, $type)
    {
        $this->assertEquals($expected, Helper::strPad($str, $len, $pad, $type));
    }

    public function dataGetTaskInfos()
    {
        return [
            [StubTask::class, 'mainAction', [
                'description' => 'StubTask::mainAction',
                'arguments'   => [
                    'abc : abc Arg',
                    'xyz : xyz Arg',
                ],
                'options'     => [
                    '-o1, --opt_1 : Option one',
                    '-o2, --opt_2 : Option two',
                ],
            ]],
            [StubTask::class, 'testAction', ['description' => 'StubTask::testAction']]
        ];
    }

    /**
     * @dataProvider dataGetTaskInfos
     */
    public function testGetTaskInfos($class, $action, $infos)
    {
        $this->assertEquals($infos, Helper::getTaskInfos($class, $action));
    }
}
