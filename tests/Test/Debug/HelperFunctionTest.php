<?php

namespace Test\Debug;

use Neutrino\Foundation\Debug;
use PHPUnit\Framework\TestCase;

class HelperFunctionTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        require __DIR__ . '/../../../src/Neutrino/Foundation/Debug/resources/helpers.php';
    }

    private function assertFunctionExist($function)
    {
        $this->assertTrue(function_exists('Neutrino\Foundation\Debug\\' . $function));
    }

    public function testMtime()
    {
        $this->assertFunctionExist('human_mtime');

        $this->assertEquals('0.23 ns', Debug\human_mtime(0.00000000023));
        $this->assertEquals('1 ns', Debug\human_mtime(0.000000001));
        $this->assertEquals('1.23 ns', Debug\human_mtime(0.00000000123));
        $this->assertEquals('101.2 ns', Debug\human_mtime(0.00000010123));
        $this->assertEquals('101.23 ns', Debug\human_mtime(0.00000010123, 3));
        $this->assertEquals('10.1 µs', Debug\human_mtime(0.00001010123));
        $this->assertEquals('10.101 µs', Debug\human_mtime(0.00001010123, 3));
        $this->assertEquals('1.01 ms', Debug\human_mtime(0.00101010123));
        $this->assertEquals('1.01 ms', Debug\human_mtime(0.00101010123, 3));
        $this->assertEquals('101 ms', Debug\human_mtime(0.10101010123));
        $this->assertEquals('101.01 ms', Debug\human_mtime(0.10101010123, 3));
        $this->assertEquals('1101 ms', Debug\human_mtime(1.10101010123));
        $this->assertEquals('1101.01 ms', Debug\human_mtime(1.10101010123, 3));
        $this->assertEquals('61101 ms', Debug\human_mtime(61.10101010123));
        $this->assertEquals('61101.01 ms', Debug\human_mtime(61.10101010123, 3));
    }

    public function testBytes()
    {
        $this->assertFunctionExist('human_bytes');

        $this->assertEquals('128 B', Debug\human_bytes(128));
        $this->assertEquals('1 KB', Debug\human_bytes(1024));
        $this->assertEquals('2 KB', Debug\human_bytes(1024 * 2));
        $this->assertEquals('2.5 KB', Debug\human_bytes(1024 * 2 + 512));
        $this->assertEquals('2.56 KB', Debug\human_bytes(1024 * 2 + 512 + 64));
        $this->assertEquals('2.563 KB', Debug\human_bytes(1024 * 2 + 512 + 64, 3));
        $this->assertEquals('1 MB', Debug\human_bytes(1024 ** 2));
        $this->assertEquals('1 GB', Debug\human_bytes(1024 ** 3));
        $this->assertEquals('1 TB', Debug\human_bytes(1024 ** 4));
        $this->assertEquals('1024 TB', Debug\human_bytes(1024 ** 5));
    }

    public function testSqlHighlight()
    {
        $this->markTestSkipped("Test to re write");

        $this->assertFunctionExist('sql_highlight');

        $this->assertEquals(
            '<span class="keyw">SELECT</span> * ' . PHP_EOL .
            '<span class="keyw">FROM</span> table',
            Debug\sql_highlight("SELECT * FROM table")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> * ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span>',
            Debug\sql_highlight("SELECT * FROM `table`")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> column ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span>',
            Debug\sql_highlight("SELECT column FROM `table`")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> <span class="column">`column`</span> ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span>',
            Debug\sql_highlight("SELECT `column` FROM `table`")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> <span class="table">`table`</span>.<span class="column">`column`</span> ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span>',
            Debug\sql_highlight("SELECT `table`.`column` FROM `table`")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> <span class="table">`table`</span>.<span class="column">`column`</span> ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span> ' . PHP_EOL .
            '<span class="keyw">WHERE</span> <span class="column">`column`</span> = <span class="string">\'test\'</span>'.
            '<span class="keyw"> AND </span><span class="table">`table`</span>.<span class="column">`column`</span> = <span class="string">"test"</span>',
            Debug\sql_highlight("SELECT `table`.`column` FROM `table` WHERE `column` = 'test' AND `table`.`column` = \"test\"")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> <i class="func">COUNT</i>(*)<span class="keyw"> AS </span>nb ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span> ' . PHP_EOL .
            '<span class="keyw">WHERE</span> <span class="column">`column`</span> = <span class="string">\'test\'</span>'.
            '<span class="keyw"> AND </span><span class="table">`table`</span>.<span class="column">`column`</span> = <span class="string">"test"</span>',
            Debug\sql_highlight("SELECT COUNT(*) AS nb FROM `table` WHERE `column` = 'test' AND `table`.`column` = \"test\"")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> <i class="func">COUNT</i>(*)<span class="keyw"> AS </span><span class="column">`nb`</span> ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span> ' . PHP_EOL .
            '<span class="keyw">WHERE</span> <span class="column">`column`</span> = <span class="string">\'test\'</span>'.
            '<span class="keyw"> AND </span><span class="table">`table`</span>.<span class="column">`column`</span> = <span class="string">"test"</span>',
            Debug\sql_highlight("SELECT COUNT(*) AS `nb` FROM `table` WHERE `column` = 'test' AND `table`.`column` = \"test\"")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> <i class="func">COUNT</i>(*)<span class="keyw"> AS </span><span class="column">`nb`</span> ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span> ' . PHP_EOL .
            '<span class="keyw">GROUP BY</span> <span class="column">`column`</span>',
            Debug\sql_highlight("SELECT COUNT(*) AS `nb` FROM `table` GROUP BY `column`")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> <i class="func">COUNT</i>(*)<span class="keyw"> AS </span><span class="column">`nb`</span> ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span> ' . PHP_EOL .
            '<span class="keyw">GROUP BY</span> <span class="table">`table`</span>.<span class="column">`column`</span>',
            Debug\sql_highlight("SELECT COUNT(*) AS `nb` FROM `table` GROUP BY `table`.`column`")
        );
        $this->assertEquals(
            '<span class="keyw">SELECT</span> <i class="func">COUNT</i>(*)<span class="keyw"> AS </span><span class="column">`nb`</span> ' . PHP_EOL .
            '<span class="keyw">FROM</span> <span class="table">`table`</span> ' . PHP_EOL .
            '<span class="keyw">HAVING</span> <span class="column">`nb`</span> > 1 ' . PHP_EOL .
            '<span class="keyw">GROUP BY</span> <span class="table">`table`</span>.<span class="column">`column`</span>',
            Debug\sql_highlight("SELECT COUNT(*) AS `nb` FROM `table` HAVING `nb` > 1 GROUP BY `table`.`column`")
        );
    }

    public function testFileHighlight()
    {
        $this->assertFunctionExist('file_highlight');

        $this->assertEquals(
            __DIR__ . DIRECTORY_SEPARATOR . '<b>' . basename(__FILE__) . '</b>',
            Debug\file_highlight(__DIR__ . DIRECTORY_SEPARATOR . basename(__FILE__))
        );
        $this->assertEquals(
            '<b>' . basename(__FILE__) . '</b>',
            Debug\file_highlight(BASE_PATH . DIRECTORY_SEPARATOR . basename(__FILE__))
        );
        $this->assertEquals(
            'app' . DIRECTORY_SEPARATOR . '<b>' . basename(__FILE__) . '</b>',
            Debug\file_highlight(BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . basename(__FILE__))
        );
    }
    public function testFuncHighlight()
    {
        $this->assertFunctionExist('func_highlight');

        $this->assertEquals(
            '<code style="color:#880000">Test\Debug\HelperFunctionTest</code><code>::</code><code style="color:#880000;font-weight:bold">testFuncHighlight</code><code>(</code><code>.</code><code>.</code><code>.</code><code style="color:#880000">$args</code><code>)</code>',
            Debug\func_highlight(__METHOD__.'(...$args)')
        );
    }
}
