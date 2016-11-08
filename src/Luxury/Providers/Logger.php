<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Phalcon\Logger\Adapter\Database as DatabaseLoggerAdapter;
use Phalcon\Logger\Adapter\File as FileLoggerAdapter;
use Phalcon\Logger\Adapter\File\Multiple as MultipleLoggerAdapter;

/**
 * Class Logger
 *
 * @package Luxury\Foundation\Bootstrap
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
        $config = $this->getDI()->getShared(Services::CONFIG);

        $adapter = ucfirst(isset($config->log->adapter) ? $config->log->adapter : 'empty');
        switch ($adapter) {
            case null:
            case MultipleLoggerAdapter::class:
            case 'Multiple':
                $adapter = MultipleLoggerAdapter::class;

                $name = isset($config->log->path) ? $config->log->path :  null;
                break;
            case FileLoggerAdapter::class:
            case 'File':
                $adapter = FileLoggerAdapter::class;

                $name = isset($config->log->path) ? $config->log->path :  null;
                break;

            case DatabaseLoggerAdapter::class:
            case 'Database':
                $adapter = DatabaseLoggerAdapter::class;

                $config->log->options->db = $this->getDI()->getShared(Services::DB);
                $name = isset($config->log->name) ? $config->log->name :  'phalcon';
                break;
            case 'Firelogger':
            case 'Stream':
            case 'Syslog':
            case 'Udplogger':
                $adapter = '\Phalcon\Logger\Adapter\\' . $adapter;

                $name = isset($config->log->name) ? $config->log->name :  'phalcon';
                break;
            default:
                if(!class_exists($adapter)){
                    throw new \RuntimeException("Logger adapter $adapter not implemented.");
                }

                $name = isset($config->log->name) ? $config->log->name :  'phalcon';
        }

        if (empty($name)) {
            throw new \RuntimeException('Required parameter {name|path} missing.');
        }

        if (empty($config->log->options)) {
            throw new \RuntimeException('Required parameter {options} missing.');
        }

        return new $adapter($name, $config->log->options->toArray());
    }
}
