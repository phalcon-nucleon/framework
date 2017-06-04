<?php

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Support\Arr;
use Phalcon\Di;
use Phalcon\Logger\Formatter;
use Phalcon\Logger\Formatter\Line as FormatterLine;

/**
 * Class Logger
 *
 * @package     Neutrino\Error\Writer
 */
class Logger implements Writable
{

    public function handle(Error $error)
    {
        $di = Di::getDefault();
        if ($di && $di->has(Services::LOGGER)) {
            /* @var \Phalcon\Logger\Adapter $logger */
            /* @var \Phalcon\Config $config */
            $logger = $di->getShared(Services::LOGGER);

            $config = [];
            if ($di->has(Services::CONFIG)) {
                $config = $di->getShared(Services::CONFIG);
            }

            if (Arr::has($config, 'error.formatter')) {
                $configFormat = Arr::get($config, 'error.formatter');
                $formatter    = null;

                if ($configFormat instanceof Formatter) {
                    $formatter = $configFormat;
                } elseif (is_array($configFormat)) {
                    $formatterOpts = $configFormat;
                    $format        = null;
                    $dateFormat    = null;

                    if (isset($formatter['format'])) {
                        $format = $formatter['format'];
                    }

                    if (isset($formatterOpts['dateFormat'])) {
                        $dateFormat = $formatterOpts['dateFormat'];
                    } elseif (isset($formatterOpts['date_format'])) {
                        $dateFormat = $formatterOpts['date_format'];
                    } elseif (isset($formatterOpts['date'])) {
                        $dateFormat = $formatterOpts['date'];
                    }

                    $formatter = new FormatterLine($format, $dateFormat);
                }

                if ($formatter) {
                    $logger->setFormatter($formatter);
                }
            }

            $logger->log(static::getLogType($error->type), Helper::format($error, true, true));
        }
    }

    /**
     * Maps error code to a log type.
     *
     * @param  integer $code
     *
     * @return integer
     */
    public static function getLogType($code)
    {
        switch ($code) {
            case E_PARSE:
                return \Phalcon\Logger::CRITICAL;
            case E_COMPILE_ERROR:
            case E_CORE_ERROR:
            case E_ERROR:
                return \Phalcon\Logger::EMERGENCY;
            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
                return \Phalcon\Logger::ERROR;
            case E_WARNING:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return \Phalcon\Logger::WARNING;
            case E_NOTICE:
            case E_USER_NOTICE:
                return \Phalcon\Logger::NOTICE;
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return \Phalcon\Logger::INFO;
        }

        return \Phalcon\Logger::ERROR;
    }
}
