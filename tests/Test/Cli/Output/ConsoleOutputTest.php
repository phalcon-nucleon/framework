<?php

declare(strict_types=1);

namespace Test\Cli\Output;

use Fake\Kernels\Cli\Output\StubOutput;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Writer;

class ConsoleOutputTest extends \PHPUnit\Framework\TestCase
{
    private function writer(bool $quiet = false): Writer
    {
        return new StubOutput($quiet);
    }

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
            ["\033[32mtest\033[39m" . PHP_EOL, 'info'],
            ["\033[33mtest\033[39m" . PHP_EOL, 'notice'],
            ["\033[33;7mtest\033[39;27m" . PHP_EOL, 'warn'],
            ["\033[30;41mtest\033[39;49m" . PHP_EOL, 'error'],
            ["\033[30;46mtest\033[39;49m" . PHP_EOL, 'question'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataColorisedFunctions')]
    public function testColorisedFunctions($expected, $func): void
    {
        $output = $this->writer();

        $output->$func('test');

        $this->assertEquals($expected, $output->out);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataColorisedFunctions')]
    public function testQuiet($expected, $func): void
    {
        $output = $this->writer(true);

        $output->$func('test');

        $output->clean();

        $this->assertSame('', $output->out);
    }
}
