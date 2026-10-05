<?php

declare(strict_types=1);

namespace Test\Providers;

use Neutrino\Constants\Services;
use Neutrino\Providers\Logger;
use Phalcon\Logger\Adapter\Noop;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Logger\Adapter\Syslog;
use Phalcon\Logger\Formatter\Json;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\Logger as PhalconLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

final class LoggerProviderTest extends ProvidersTestCase
{
    private string $file = '';

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir() . '/nucleon-log-' . bin2hex(random_bytes(4)) . '.log';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);

        parent::tearDown();
    }

    public function testStream(): void
    {
        $logger = $this->logger(['adapters' => ['main' => ['adapter' => 'stream', 'path' => $this->file]]]);

        $this->assertSame('nucleon', $logger->getName());
        $this->assertInstanceOf(Stream::class, $logger->getAdapter('main'));
        $this->assertInstanceOf(Line::class, $logger->getAdapter('main')->getFormatter());

        // Phalcon 5: the context placeholders use the interpolators of the format (%what%), no longer {what}.
        $logger->error('Something %what%', ['what' => 'failed']);
        $logger->getAdapter('main')->close();

        $this->assertMatchesRegularExpression('/^\[[^\]]+\]\[error\] Something failed$/m', (string) file_get_contents($this->file));
    }

    public function testServiceIsSharedAndAliased(): void
    {
        $di = $this->container([Logger::class], ['log' => ['adapters' => [['adapter' => 'noop']]]]);

        $this->assertTrue($di->getService(Services::LOGGER)->isShared());
        $this->assertSame($di->getShared(Services::LOGGER), $di->getShared(PhalconLogger::class));
    }

    public function testSeveralAdapters(): void
    {
        $logger = $this->logger([
            'name'      => 'app',
            'level'     => 'warning',
            'formatter' => ['formatter' => 'line', 'format' => '%level%: %message%'],
            'adapters'  => [
                'main'   => ['adapter' => 'stream', 'path' => $this->file],
                'json'   => ['adapter' => 'stream', 'path' => $this->file, 'formatter' => 'json'],
                'syslog' => ['adapter' => 'syslog', 'name' => 'nucleon-test', 'options' => ['facility' => LOG_USER]],
                'noop'   => 'noop',
            ],
        ]);

        $this->assertSame('app', $logger->getName());
        $this->assertSame(\Phalcon\Logger\Enum::WARNING, $logger->getLogLevel());
        $this->assertSame(['main', 'json', 'syslog', 'noop'], array_keys($logger->getAdapters()));
        $this->assertInstanceOf(Json::class, $logger->getAdapter('json')->getFormatter());
        $this->assertInstanceOf(Syslog::class, $logger->getAdapter('syslog'));
        $this->assertInstanceOf(Noop::class, $logger->getAdapter('noop'));

        $logger->excludeAdapters(['syslog'])->info('ignored');
        $logger->excludeAdapters(['syslog'])->error('written');
        $logger->getAdapter('main')->close();
        $logger->getAdapter('json')->close();

        $lines = file($this->file, FILE_IGNORE_NEW_LINES) ?: [];
        $this->assertCount(2, $lines);
        $this->assertContains('error: written', $lines);
        $this->assertSame('written', json_decode($lines[0] === 'error: written' ? $lines[1] : $lines[0], true)['message'] ?? null);
    }

    public function testCustomAdapter(): void
    {
        $logger = $this->logger(['adapters' => ['main' => ['adapter' => Stream::class, 'path' => $this->file]]]);

        $this->assertInstanceOf(Stream::class, $logger->getAdapter('main'));
        $this->assertSame($this->file, $logger->getAdapter('main')->getName());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string}>
     */
    public static function legacyConfigs(): iterable
    {
        yield 'File' => [['adapter' => 'File', 'path' => 'php://memory', 'options' => []], Stream::class];
        yield 'Stream' => [['adapter' => 'Stream', 'name' => 'php://memory', 'options' => []], Stream::class];
        yield 'Syslog' => [['adapter' => 'Syslog', 'name' => 'nucleon'], Syslog::class];
        yield 'no adapter, a path' => [['path' => 'php://memory', 'options' => []], Stream::class];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('legacyConfigs')]
    public function testLegacySingleAdapter(array $config, string $class): void
    {
        $logger = $this->logger($config);

        $this->assertSame('nucleon', $logger->getName());
        $this->assertSame(['main'], array_keys($logger->getAdapters()));
        $this->assertInstanceOf($class, $logger->getAdapter('main'));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidConfigs(): iterable
    {
        yield 'no config' => [[], 'no adapter, set "log.adapters"'];
        yield 'empty adapters' => [['adapters' => []], 'must list at least one adapter'];
        yield 'unknown adapter' => [['adapters' => ['main' => ['adapter' => 'wrong']]], 'Logger adapter "main": unknown adapter "wrong"'];
        yield 'removed adapter' => [['adapter' => 'Firelogger'], 'the Firelogger adapter was removed with Phalcon 5'];
        yield 'stream without path' => [['adapters' => ['main' => ['adapter' => 'stream']]], '"path" is required'];
        yield 'unknown formatter' => [['adapters' => [['adapter' => 'noop', 'formatter' => 'xml']]], 'unknown formatter'];
        yield 'unknown level' => [['level' => 'loud', 'adapters' => ['noop']], 'unknown level'];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('invalidConfigs')]
    public function testInvalidConfig(array $config, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->logger($config);
    }

    /**
     * @param array<string, mixed> $log
     */
    private function logger(array $log): PhalconLogger
    {
        $logger = $this->container([Logger::class], ['log' => $log])->getShared(Services::LOGGER);
        $this->assertInstanceOf(PhalconLogger::class, $logger);

        return $logger;
    }
}
