<?php

declare(strict_types=1);

namespace Test\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer\Logger;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Logger\Enum;
use Phalcon\Logger\Formatter\Json;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\Logger as PhalconLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase\TestCase;

final class LoggerTest extends TestCase
{
    private string $file = '';

    protected function tearDown(): void
    {
        self::getConfig()->remove('error');

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = self::$cache_dir . 'error.log';
    }

    /**
     * @return iterable<array{int, string}>
     */
    public static function levels(): iterable
    {
        yield [E_ERROR, 'emergency'];
        yield [E_PARSE, 'critical'];
        yield [E_USER_ERROR, 'error'];
        yield [E_WARNING, 'warning'];
        yield [E_NOTICE, 'notice'];
        yield [E_DEPRECATED, 'info'];
    }

    #[DataProvider('levels')]
    public function testLevel(int $type, string $level): void
    {
        $this->logger(new Line('%level% %message%'));
        $error = Error::fromError($type, 'msg', '/app/file.php', 3);

        (new Logger())->handle($error);

        // Phalcon writes the line breaks of a message as "\x0A": an error is on one line.
        $this->assertSame($level . ' ' . self::escaped(Helper::format($error)) . PHP_EOL, file_get_contents($this->file));
    }

    public function testFormatterOfTheConfig(): void
    {
        $logger = $this->logger(new Line('%level% %message%'));
        $this->getDI()->getShared(Services::CONFIG)->merge(new \Neutrino\Config\Config(['error' => ['formatter' => ['formatter' => 'line', 'format' => '[error] %message%']]]));

        (new Logger())->handle(Error::fromError(E_WARNING, 'first'));
        $logger->info('after');

        // The formatter is used for the error only.
        $this->assertSame(
            '[error] ' . self::escaped(Helper::format(Error::fromError(E_WARNING, 'first'))) . PHP_EOL . 'info after' . PHP_EOL,
            file_get_contents($this->file),
        );
        $this->assertInstanceOf(Line::class, $logger->getAdapter('main')->getFormatter());
    }

    public function testFormatterInstance(): void
    {
        $this->logger(new Line('%message%'));
        $this->getDI()->getShared(Services::CONFIG)->merge(new \Neutrino\Config\Config(['error' => ['formatter' => new Json()]]));

        (new Logger())->handle(Error::fromError(E_WARNING, 'msg'));

        $this->assertSame('warning', json_decode((string) file_get_contents($this->file), true)['level'] ?? null);
    }

    public function testWithoutLogger(): void
    {
        $this->getDI()->remove(Services::LOGGER);

        $this->expectOutputString('');

        (new Logger())->handle(Error::fromError(E_WARNING, 'msg'));
    }

    private static function escaped(string $message): string
    {
        return str_replace("\n", '\x0A', $message);
    }

    private function logger(Line $formatter): PhalconLogger
    {
        $adapter = new Stream($this->file);
        $adapter->setFormatter($formatter);

        $logger = new PhalconLogger('test', ['main' => $adapter]);
        $logger->setLogLevel(Enum::CUSTOM);
        $this->mockService(Services::LOGGER, $logger);

        return $logger;
    }
}
