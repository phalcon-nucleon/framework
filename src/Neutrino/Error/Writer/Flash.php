<?php

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Phalcon\Di;
use Phalcon\Logger;

/**
 * Class Flash
 *
 * @package     Neutrino\Error\Writer
 */
class Flash implements Writable
{

    public function handle(Error $error)
    {
        $di = Di::getDefault();

        if (!is_null($di) && $di->has(Services::FLASH)) {
            /** @var \Phalcon\Flash\Direct $flash */

            $flash = $di->getShared(Services::FLASH);

            switch (Helper::getLogType($error->type)) {
                case Logger::CRITICAL:
                case Logger::EMERGENCY:
                case Logger::ERROR:
                    $flash->error(Helper::format($error, false, true));
                    break;
                case Logger::WARNING:
                    $flash->warning(Helper::format($error, false, true));
                    break;
                case Logger::NOTICE:
                case Logger::INFO:
                    $flash->notice(Helper::format($error, false, true));
                    break;
            }
        }
    }
}
