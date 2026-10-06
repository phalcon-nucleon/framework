<?php

declare(strict_types=1);

namespace Test\Error\Writer;

use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer\Cli;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase\TestCase;

final class CliTest extends TestCase
{
    /**
     * @return iterable<string, array{Error, string}>
     */
    public static function errors(): iterable
    {
        yield 'exception' => [Error::fromException(new \Exception('boom')), 'error'];
        yield 'E_ERROR' => [Error::fromError(E_ERROR, 'msg', __FILE__, 1), 'error'];
        yield 'E_USER_ERROR' => [Error::fromError(E_USER_ERROR, 'msg', __FILE__, 1), 'error'];
        yield 'E_WARNING' => [Error::fromError(E_WARNING, 'msg', __FILE__, 1), 'warn'];
        yield 'E_NOTICE' => [Error::fromError(E_NOTICE, 'msg', __FILE__, 1), 'notice'];
        yield 'E_DEPRECATED' => [Error::fromError(E_DEPRECATED, 'msg', __FILE__, 1), 'info'];
    }

    #[DataProvider('errors')]
    public function testBlockOfTheSeverity(Error $error, string $style): void
    {
        $output = $this->mockService(Services\Cli::OUTPUT, Writer::class);
        $lines = [];
        $output->expects($this->once())->method('line')->with('');
        $output->method($style)->willReturnCallback(function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        (new Cli())->handle($error);

        // Blank first and last rows, then the lines of the error padded by 2 spaces (split at 100 characters).
        $this->assertSame('', trim($lines[0]));
        $this->assertSame('', trim($lines[count($lines) - 1]));
        $this->assertSame(
            str_replace("\n", '', Helper::format($error)),
            implode('', array_map(static fn(string $line): string => rtrim(substr($line, 2)), array_slice($lines, 1, -1))),
        );
    }

    public function testWithoutOutputService(): void
    {
        $this->getDI()->remove(Services\Cli::OUTPUT);
        $error = Error::fromError(E_WARNING, 'msg', __FILE__, 1);

        $this->expectOutputString(Helper::format($error) . "\n");

        (new Cli())->handle($error);
    }
}
