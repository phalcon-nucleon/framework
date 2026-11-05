<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Config\Config;
use Phalcon\Logger\Adapter\AdapterInterface;
use Phalcon\Logger\Enum;
use Phalcon\Logger\Adapter\Noop;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Logger\Adapter\Syslog;
use Phalcon\Logger\Formatter\FormatterInterface;
use Phalcon\Logger\Formatter\Json;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\Logger as PhalconLogger;
use RuntimeException;

/**
 * The `logger` service: a {@see PhalconLogger} writing to the adapters of `log.adapters`.
 *
 * ```php
 * 'log' => [
 *     'name'      => 'nucleon',                           // optional
 *     'level'     => 'info',                              // optional: lowest level written
 *     'formatter' => 'line',                              // `line` (default), `json`, a class, or
 *                                                         // ['formatter' => 'line', 'format' => '…', 'date_format' => 'c']
 *     'adapters'  => [
 *         'main'   => ['adapter' => 'stream', 'path' => BASE_PATH . '/storage/logs/app.log'],
 *         'syslog' => ['adapter' => 'syslog', 'name' => 'nucleon', 'options' => [...], 'formatter' => 'json'],
 *     ],
 * ],
 * ```
 *
 * An adapter is `stream` (`path`), `syslog` (`name`), `noop`, or a class implementing {@see AdapterInterface},
 * built with `new $class($path ?? $name, $options)`.
 *
 * The single adapter form of Nucleon 1.3 (`log.adapter`, `log.path` or `log.name`, `log.options`) is converted.
 */
class Logger extends Provider
{
    protected string $name = Services::LOGGER;

    protected bool $shared = true;

    protected array $aliases = [PhalconLogger::class];

    /**
     * Adapters of Nucleon 1.3 removed with Phalcon 5.
     *
     * @var list<string>
     */
    private const array REMOVED = ['firelogger', 'udplogger', 'multiple'];

    protected function register(): PhalconLogger
    {
        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);
        $log = $config->path('log');
        $log = $log instanceof Config ? $log->toArray() : [];

        // Nucleon 1.3 used `log.name` as the name of its single adapter.
        $name = isset($log['adapters']) && is_string($log['name'] ?? null) ? $log['name'] : 'nucleon';
        $adapters = [];

        foreach (self::adaptersConfig($log) as $key => $adapter) {
            $adapters[(string) $key] = $this->makeAdapter((string) $key, $adapter, $log['formatter'] ?? null);
        }

        $logger = new PhalconLogger($name, $adapters);

        if (isset($log['level'])) {
            $logger->setLogLevel(self::level($log['level']));
        }

        return $logger;
    }

    /**
     * @param array<mixed> $log
     *
     * @return array<array<mixed>>
     */
    private static function adaptersConfig(array $log): array
    {
        if (isset($log['adapters'])) {
            if (!is_array($log['adapters']) || $log['adapters'] === []) {
                throw new RuntimeException('Logger: "log.adapters" must list at least one adapter.');
            }

            return array_map(static fn(mixed $adapter): array => is_array($adapter) ? $adapter : ['adapter' => $adapter], $log['adapters']);
        }

        // Nucleon 1.3: a single adapter, a file on `log.path` when not set.
        if (!isset($log['adapter']) && !is_string($log['path'] ?? null)) {
            throw new RuntimeException('Logger: no adapter, set "log.adapters".');
        }

        return ['main' => [
            'adapter' => match ($log['adapter'] ?? null) {
                null, 'File', 'Phalcon\\Logger\\Adapter\\File' => 'stream',
                default => $log['adapter'],
            },
            'path'    => $log['path'] ?? null,
            'name'    => $log['name'] ?? null,
            'options' => $log['options'] ?? [],
        ]];
    }

    /**
     * @param array<mixed> $config
     */
    private function makeAdapter(string $key, array $config, mixed $formatter): AdapterInterface
    {
        $type = $config['adapter'] ?? null;
        if (!is_string($type) || $type === '') {
            throw new RuntimeException("Logger adapter \"$key\": no adapter.");
        }

        $options = $config['options'] ?? [];
        if (!is_array($options)) {
            throw new RuntimeException("Logger adapter \"$key\": \"options\" must be an array.");
        }

        $path = is_string($config['path'] ?? null) && $config['path'] !== '' ? $config['path'] : null;
        $name = is_string($config['name'] ?? null) && $config['name'] !== '' ? $config['name'] : null;

        $adapter = match (strtolower($type)) {
            'stream' => new Stream($path ?? $name ?? throw new RuntimeException("Logger adapter \"$key\": \"path\" is required."), $options), // @phpstan-ignore argument.type (options of the config, checked by Phalcon)
            'syslog' => new Syslog($name ?? 'nucleon', $options), // @phpstan-ignore argument.type (options of the config, checked by Phalcon)
            'noop'   => new Noop(),
            default  => $this->customAdapter($key, $type, $path ?? $name ?? 'nucleon', $options),
        };

        $adapter->setFormatter(self::formatter($key, $config['formatter'] ?? $formatter));

        return $adapter;
    }

    /**
     * @param array<mixed> $options
     */
    private function customAdapter(string $key, string $class, string $name, array $options): AdapterInterface
    {
        if (in_array(strtolower($class), self::REMOVED, true)) {
            throw new RuntimeException("Logger adapter \"$key\": the $class adapter was removed with Phalcon 5. Use stream, syslog or noop.");
        }

        if (!class_exists($class) || !is_subclass_of($class, AdapterInterface::class)) {
            throw new RuntimeException("Logger adapter \"$key\": unknown adapter \"$class\". Use stream, syslog, noop or a class implementing " . AdapterInterface::class . '.');
        }

        return new $class($name, $options);
    }

    /**
     * @internal Also used by the error writer `Neutrino\Error\Writer\Logger` (`error.formatter`).
     */
    public static function formatter(string $key, mixed $config): FormatterInterface
    {
        if ($config instanceof FormatterInterface) {
            return $config;
        }

        $config = is_array($config) ? $config : ['formatter' => $config];
        $type = $config['formatter'] ?? 'line';
        $format = is_string($config['format'] ?? null) ? $config['format'] : null;
        $date = $config['date_format'] ?? $config['dateFormat'] ?? null;
        $date = is_string($date) ? $date : 'c';

        return match (is_string($type) ? strtolower($type) : $type) {
            'line'  => $format === null ? new Line(dateFormat: $date) : new Line($format, $date),
            'json'  => new Json($date),
            default => is_string($type) && class_exists($type) && is_subclass_of($type, FormatterInterface::class)
                ? new $type()
                : throw new RuntimeException("Logger adapter \"$key\": unknown formatter, use line, json or a class implementing " . FormatterInterface::class . '.'),
        };
    }

    private static function level(mixed $level): int
    {
        if (is_int($level)) {
            return $level;
        }

        $constant = Enum::class . '::' . strtoupper(is_string($level) ? $level : '');

        if (!is_string($level) || !defined($constant)) {
            throw new RuntimeException('Logger: unknown level in "log.level".');
        }

        /** @var int */
        return constant($constant);
    }
}
