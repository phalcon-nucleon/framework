<?php

declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 07/11/2016
 * Time: 10:49
 */

namespace Test\Cli\Output;

use Neutrino\Cli\Output\Decorate;

class DecorateTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Decorate::setColorSupport(true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Decorate::setColorSupport(null);
    }

    public static function dataColorisedFunctions(): array
    {
        return [
            ["\033[32mtest\033[39m", 'info'],
            ["\033[33mtest\033[39m", 'notice'],
            ["\033[33;7mtest\033[39;27m", 'warn'],
            ["\033[30;41mtest\033[39;49m", 'error'],
            ["\033[30;46mtest\033[39;49m", 'question'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataColorisedFunctions')]
    public function testColorisedFunctions($expected, $func): void
    {
        $this->assertEquals($expected, Decorate::$func('test'));
    }
}
