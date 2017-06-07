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
                } elseif (is_array($configFormat) || $configFormat instanceof \Phalcon\Config) {
                    $formatter  = FormatterLine::class;
                    $format     = null;
                    $dateFormat = null;

                    if (isset($configFormat['formatter'])) {
                        $formatter = $configFormat['formatter'];
                    }

                    if (isset($configFormat['format'])) {
                        $format = $configFormat['format'];
                    }

                    if (isset($configFormat['dateFormat'])) {
                        $dateFormat = $configFormat['dateFormat'];
                    } elseif (isset($configFormat['date_format'])) {
                        $dateFormat = $configFormat['date_format'];
                    } elseif (isset($configFormat['date'])) {
                        $dateFormat = $configFormat['date'];
                    }

                    $formatter = new $formatter($format, $dateFormat);
                }

                if ($formatter) {
                    $logger->setFormatter($formatter);
                }
            }

            $logger->log(Helper::getLogType($error->type), Helper::format($error, true, true));
        }
    }

}
