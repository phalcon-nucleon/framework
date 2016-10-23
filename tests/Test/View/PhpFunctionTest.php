<?php

namespace Test\View;

use Luxury\View\Engine\Extensions\PhpFunction;
use Test\TestCase\TestCase;

/**
 * Class PhpFunctionTest
 *
 * @package Test\View
 */
class PhpFunctionTest extends TestCase
{

    /**
     * @return array
     */
    public function dataCompile()
    {
        return [
            ['substr', "'abc', 0", "substr('abc', 0)"],
            ['strlen', "'abc'", "strlen('abc')"],
            ['not_a_function', "'abc'", null],
        ];
    }

    /**
     * @dataProvider dataCompile
     */
    public function testCompileFunction($function, $args, $excepted)
    {
        $this->assertEquals($excepted, (new PhpFunction())->compileFunction($function, $args));
    }
}
