<?php

namespace Test\Cli\Output;

use Fake\Kernels\Cli\Tasks\StubTask;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Helper;
use Phalcon;

class HelperTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        Decorate::setColorSupport(true);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        Decorate::setColorSupport(null);
    }

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

    public function dataDescribeRoutePattern()
    {
        Decorate::setColorSupport(true);

        return [
            ['', new Phalcon\Cli\Router\Route('', [])],
            ['', new Phalcon\Mvc\Router\Route('', [])],
            ['test', new Phalcon\Cli\Router\Route('test', [])],
            ['test', new Phalcon\Mvc\Router\Route('test', [])],
            ['test (\w+)', new Phalcon\Cli\Router\Route('test (\w+)', [])],
            ['test/(\w+)', new Phalcon\Mvc\Router\Route('test/(\w+)', [])],
            ['test {param_1}', new Phalcon\Cli\Router\Route('test (\w+)', ['param_1' => 1])],
            ['test ' . Decorate::notice('{param_1}'), new Phalcon\Cli\Router\Route('test (\w+)', ['param_1' => 1]), true],
            ['test/{param_1}', new Phalcon\Mvc\Router\Route('test/(\w+)', ['param_1' => 1])],
            ['test/' . Decorate::notice('{param_1}'), new Phalcon\Mvc\Router\Route('test/(\w+)', ['param_1' => 1]), true],
            [
                'test {p1} {p2}',
                new Phalcon\Cli\Router\Route('test (\w+) (\w+)', ['p1' => 1, 'p2' => 2])
            ], [
                'test/{p1}/{p2}',
                new Phalcon\Mvc\Router\Route('test/(\w+)/(\w+)', ['p1' => 1, 'p2' => 2])
            ],[
                'test ' . Decorate::notice('{p1}') . ' ' . Decorate::notice('{p2}'),
                new Phalcon\Cli\Router\Route('test (\w+) (\w+)', ['p1' => 1, 'p2' => 2]),
                true
            ], [
                'test/' . Decorate::notice('{p1}') . '/' . Decorate::notice('{p2}'),
                new Phalcon\Mvc\Router\Route('test/(\w+)/(\w+)', ['p1' => 1, 'p2' => 2]),
                true
            ], [
                'test {p1}(?: {p2})',
                new Phalcon\Cli\Router\Route('test (\w+)(?: (\w+))', ['p1' => 1, 'p2' => 2])
            ], [
                'test/{p1}/{p2}(?:/{p3})',
                new Phalcon\Mvc\Router\Route('test/(\w+)/(\w+)(?:/(\d+))', ['p1' => 1, 'p2' => 2, 'p3' => 3])
            ], [
                'test ' . Decorate::notice('{p1}') . '(?: ' . Decorate::notice('{p2}').')',
                new Phalcon\Cli\Router\Route('test (\w+)(?: (\w+))', ['p1' => 1, 'p2' => 2]),
                true
            ], [
                'test/' . Decorate::notice('{p1}') . '/' . Decorate::notice('{p2}') . '(?:/'.Decorate::notice('{p3}').')',
                new Phalcon\Mvc\Router\Route('test/(\w+)/(\w+)(?:/(\d+))', ['p1' => 1, 'p2' => 2, 'p3' => 3]),
                true
            ],
        ];
    }

    /**
     * @dataProvider dataDescribeRoutePattern
     */
    public function testDescribeRoutePattern($expected, $route, $decorate = false)
    {
        $this->assertEquals($expected, Helper::describeRoutePattern($route, $decorate));
    }
}
