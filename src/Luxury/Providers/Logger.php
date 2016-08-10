<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Luxury\Support\Arr;
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

            switch (ucfirst($adapter = Arr::fetch($config->log, 'adapter'))) {
                case null:
                case 'Multiple':
                    $adapter = \Phalcon\Logger\Adapter\File\Multiple::class;

                    $name = Arr::fetch($config->log, 'path');
                    break;
                case 'File':
                    $adapter = \Phalcon\Logger\Adapter\File::class;

                    $name = Arr::fetch($config->log, 'path');
                    break;
                case 'Database':
                case 'Firelogger':
                case 'Stream':
                case 'Syslog':
                case 'Udplogger':
                    $adapter = '\Phalcon\Logger\Adapter' . $adapter;

                    $name = Arr::fetch($config->log, 'name', 'phalcon');
                    break;
                default:
                    throw new \RuntimeException("Logger adapter $adapter not implemented.");
            }

            if (empty($name)) {
                throw new \RuntimeException('Required parameter {name|path} missing.');
            }

            return new $adapter($name, (array)Arr::fetch($config->log, 'options', []));
        });
    }
}
