<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Logger\Adapter\File as FileLoggerAdapter;

/**
 * Class Logger
 *
 *  @package Neutrino\Foundation\Bootstrap
 */
class Logger extends Provider
{
    protected $name = Services::LOGGER;

    protected $shared = true;

    /**
     * Register the logger
     *
     * @return \Phalcon\Logger\AdapterInterface
     */
    protected function register()
    {
        /** @var \Phalcon\Config|\stdClass $config */
        $config = $this->getDI()->getShared(Services::CONFIG)->log;

        $adapter = isset($config->adapter) ? $config->adapter : null;
        switch ($adapter) {
            case null:
            case FileLoggerAdapter::class:
            case 'File':
                $adapter = FileLoggerAdapter::class;

                $name = isset($config->path) ? $config->path :  null;
                break;

            case 'Firelogger':
            case 'Stream':
            case 'Syslog':
            case 'Udplogger':
                $adapter = '\Phalcon\Logger\Adapter\\' . ucfirst($adapter);

                $name = isset($config->name) ? $config->name :  'phalcon';
                break;
            default:
                if(!class_exists($adapter)){
                    throw new \RuntimeException("Logger adapter $adapter not implemented.");
                }

                $name = isset($config->name) ? $config->name : (isset($config->path) ? $config->path : 'phalcon');
        }

        if (empty($name)) {
            throw new \RuntimeException('Required parameter {name|path} missing.');
        }

        if (empty($config->options)) {
            throw new \RuntimeException('Required parameter {options} missing.');
        }

        return new $adapter($name, (array)$config->options);
    }
}
