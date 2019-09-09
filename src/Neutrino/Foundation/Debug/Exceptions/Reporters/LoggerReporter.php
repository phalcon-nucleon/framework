<?php


namespace Neutrino\Foundation\Debug\Exceptions\Reporters;

use Exception;
use Throwable;
use Neutrino\Constants\Services;
use Neutrino\Debug\Exceptions\Helper;
use Neutrino\Foundation\Debug\Exceptions\ReporterInterface;
use Neutrino\Support\Arr;
use Phalcon\Logger\Formatter;

/**
 * Class LoggerReporter
 * @package Neutrino\Foundation\Debug\Exceptions\Reporter
 */
class LoggerReporter implements ReporterInterface
{
    /**
     * @param Throwable|Exception  $throwable
     * @param \Phalcon\DiInterface $container
     *
     * @throws \Throwable
     */
    public function report($throwable, $container = null)
    {
        try {
            if ($container && $container->has(Services::LOGGER)) {
                $this->reportWithLoggerService($throwable, $container);

                return;
            }
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        // logger service isn't available, or has failed, we log to error_log.
        error_log(Helper::verbose($throwable), 0);

        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * @param Throwable|Exception  $throwable
     * @param \Phalcon\DiInterface $container
     */
    private function reportWithLoggerService($throwable, \Phalcon\DiInterface $container)
    {

        /* @var \Phalcon\Logger\Adapter $logger */
        /* @var \Phalcon\Config $config */
        $logger = $container->getShared(Services::LOGGER);

        $config = [];
        if ($container->has(Services::CONFIG)) {
            $config = $container->getShared(Services::CONFIG);
        }

        if (Arr::has($config, 'error.formatter')) {
            $configFormat = Arr::get($config, 'error.formatter');
            $formatter = null;

            if ($configFormat instanceof Formatter) {
                $formatter = $configFormat;
            } elseif (is_array($configFormat) || $configFormat instanceof \Phalcon\Config) {
                $formatter = Formatter\Line::class;
                $format = null;
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

        $logger->log(Helper::logLevel($throwable), Helper::verbose($throwable));
    }
}