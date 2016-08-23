<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Logger
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Logger implements Providable
{
    /**
     * Register the logger
     *
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::LOGGER, function () {
            /** @var \Phalcon\Di $this */
            /** @var \Phalcon\Config|\stdClass $config */
            $config = $this->getShared(Services::CONFIG);

            switch (ucfirst($adapter = ($config->log['adapter'] ?? null))) {
                case null:
                case 'Multiple':
                    $adapter = \Phalcon\Logger\Adapter\File\Multiple::class;

                    $name = $config->log['path'] ?? null;
                    break;
                case 'File':
                    $adapter = \Phalcon\Logger\Adapter\File::class;

                    $name = $config->log['path'] ?? null;
                    break;
                case 'Database':
                case 'Firelogger':
                case 'Stream':
                case 'Syslog':
                case 'Udplogger':
                    $adapter = '\Phalcon\Logger\Adapter' . $adapter;

                    $name = $config->log['name'] ?? 'phalcon';
                    break;
                default:
                    throw new \RuntimeException("Logger adapter $adapter not implemented.");
            }

            if (empty($name)) {
                throw new \RuntimeException('Required parameter {name|path} missing.');
            }

            return new $adapter($name, (array)($config->log['options'] ?? []));
        });
    }
}
