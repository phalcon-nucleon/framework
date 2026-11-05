<?php

declare(strict_types=1);

namespace Neutrino\Micro;

/**
 * When a Micro middleware runs: before the handler, after it, or once the response is ready.
 */
enum MiddlewarePosition
{
    case Before;
    case After;
    case Finish;
}
