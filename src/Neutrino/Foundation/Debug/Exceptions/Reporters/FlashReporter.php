<?php


namespace Neutrino\Foundation\Debug\Exceptions\Reporters;

use Neutrino\Constants\Services;
use Neutrino\Debug\Exceptions\Helper;
use Neutrino\Foundation\Debug\Exceptions\ReporterInterface;
use Phalcon\Logger;

/**
 * Class FlashReporter
 * @package Neutrino\Foundation\Debug\Exceptions\Reporter
 */
class FlashReporter implements ReporterInterface
{
    /**
     * @param \Throwable|\Exception $throwable
     * @param \Phalcon\DiInterface  $container
     */
    public function report($throwable, $container = null)
    {
        if (!APP_DEBUG) {
            return;
        }

        if (!is_null($container) && $container->has(Services::FLASH)) {
            /** @var \Phalcon\Flash\Direct $flash */

            $flash = $container->getShared(Services::FLASH);

            switch (Helper::logLevel($throwable)) {
                case Logger::CRITICAL:
                case Logger::EMERGENCY:
                case Logger::ERROR:
                    $flash->error(Helper::verbose($throwable));
                    break;
                case Logger::WARNING:
                    $flash->warning(Helper::verbose($throwable));
                    break;
                case Logger::NOTICE:
                case Logger::INFO:
                default:
                    $flash->notice(Helper::verbose($throwable));
            }
        }
    }
}