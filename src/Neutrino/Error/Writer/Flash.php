<?php

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Phalcon\Di;
use Phalcon\Logger as Phogger;

/**
 * Class Flash
 *
 * @package     Neutrino\Error\Writer
 */
class Flash implements Writable
{
    /**
     * @inheritdoc
     */
    public function handle(Error $error)
    {
        if (!APP_DEBUG) {
            return;
        }

        $di = Di::getDefault();

        if (!is_null($di) && $di->has(Services::FLASH)) {
            /** @var \Phalcon\Flash\Direct $flash */

            $flash = $di->getShared(Services::FLASH);

            switch (Helper::getLogType($error->type)) {
                case Phogger::CRITICAL:
                case Phogger::EMERGENCY:
                case Phogger::ERROR:
                    $flash->error(Helper::format($error));
                    break;
                case Phogger::WARNING:
                    $flash->warning(Helper::format($error));
                    break;
                case Phogger::NOTICE:
                case Phogger::INFO:
                    $flash->notice(Helper::format($error));
                    break;
            }
        }
    }
}
