<?php

declare(strict_types=1);

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Providers\Logger as LoggerProvider;
use Phalcon\Config\ConfigInterface;
use Phalcon\Di\Di;
use Phalcon\Logger\LoggerInterface;

/**
 * Writes the errors to the `logger` service, at the level of their type.
 *
 * `error.formatter` formats the errors with another formatter than the adapters' one (same values as
 * `log.formatter`): it is set on every adapter for the error only.
 */
final class Logger implements Writable
{
    public function handle(Error $error): void
    {
        $di = Di::getDefault();
        $logger = $di !== null && $di->has(Services::LOGGER) ? $di->getShared(Services::LOGGER) : null;

        if (!$logger instanceof LoggerInterface) {
            return;
        }

        $config = $di->has(Services::CONFIG) ? $di->getShared(Services::CONFIG) : null;
        $formatter = $config instanceof ConfigInterface ? $config->path('error.formatter') : null;

        if ($formatter === null) {
            $logger->log($error->logLvl, Helper::format($error));

            return;
        }

        $formatter = LoggerProvider::formatter('error.formatter', $formatter instanceof ConfigInterface ? $formatter->toArray() : $formatter);
        $adapters = $logger->getAdapters();
        $previous = array_map(static fn($adapter) => $adapter->getFormatter(), $adapters);

        try {
            foreach ($adapters as $adapter) {
                $adapter->setFormatter($formatter);
            }

            $logger->log($error->logLvl, Helper::format($error));
        } finally {
            foreach ($adapters as $name => $adapter) {
                $adapter->setFormatter($previous[$name]);
            }
        }
    }
}
