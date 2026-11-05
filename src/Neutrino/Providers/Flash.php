<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Support\Provider;
use Phalcon\Flash\Direct;
use Phalcon\Html\Escaper\EscaperInterface;

/**
 * The `flash` service: messages returned instead of printed (`setImplicitFlush(false)`), a new instance each time.
 */
class Flash extends Provider
{
    protected string $name = Services::FLASH;

    protected bool $shared = false;

    protected array $aliases = [Direct::class];

    protected function register(): Direct
    {
        $di = $this->getDI();
        /** @var EscaperInterface|null $escaper */
        $escaper = $di->has(Services::ESCAPER) ? $di->getShared(Services::ESCAPER) : null;

        $flash = new Direct($escaper);
        $flash->setImplicitFlush(false);

        return $flash;
    }
}
