<?php

declare(strict_types=1);

namespace Neutrino\Micro;

use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\MiddlewareInterface;

/**
 * Middleware of the Micro kernel (`$middlewares`), bound at the position given by {@see bindOn()}.
 *
 * In position Before, `call()` returning `false` stops the request.
 */
abstract class Middleware implements MiddlewareInterface
{
    abstract public function bindOn(): MiddlewarePosition;

    /**
     * @return bool|null|void
     */
    abstract public function call(Micro $application);
}
