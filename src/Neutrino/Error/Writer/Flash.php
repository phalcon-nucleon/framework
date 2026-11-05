<?php

declare(strict_types=1);

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Phalcon\Di\Di;
use Phalcon\Flash\FlashInterface;
use Phalcon\Logger\Enum;

/**
 * Shows the non-fatal errors in a flash message (`flash` service), in debug mode only.
 *
 * The fatal errors are left to the error page (`View` writer): a flash message output before it would
 * send the headers, and the page would lose its 500 status.
 */
final class Flash implements Writable
{
    public function handle(Error $error): void
    {
        if (!APP_DEBUG || $error->isFatal()) {
            return;
        }

        $di = Di::getDefault();

        if ($di === null || !$di->has(Services::FLASH) || !($flash = $di->getShared(Services::FLASH)) instanceof FlashInterface) {
            return;
        }

        match ($error->logLvl) {
            Enum::WARNING         => $flash->warning(Helper::format($error)),
            Enum::NOTICE, Enum::INFO, Enum::DEBUG => $flash->notice(Helper::format($error)),
            default               => $flash->error(Helper::format($error)),
        };
    }
}
