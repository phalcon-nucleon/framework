<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Flash\Session as PhalconFlashSession;
use Phalcon\Html\Escaper\EscaperInterface;

/**
 * The `flashSession` service: messages kept in the session until they are output.
 *
 * The session is read from the container when a message is stored or output, not when the service is built.
 */
class FlashSession extends Provider
{
    protected string $name = Services::FLASH_SESSION;

    protected bool $shared = true;

    protected array $aliases = [PhalconFlashSession::class];

    protected function register(): PhalconFlashSession
    {
        $di = $this->getDI();
        /** @var EscaperInterface|null $escaper */
        $escaper = $di->has(Services::ESCAPER) ? $di->getShared(Services::ESCAPER) : null;

        return new PhalconFlashSession($escaper);
    }
}
