<?php

declare(strict_types=1);

namespace Test\Error\Writer;

use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;

/**
 * A response that records its sending instead of outputting it.
 */
final class StubResponse extends Response
{
    public bool $wasSent = false;

    public function send(): ResponseInterface
    {
        $this->wasSent = true;

        return $this;
    }

    public function isSent(): bool
    {
        return $this->wasSent;
    }
}
