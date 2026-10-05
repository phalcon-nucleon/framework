<?php

declare(strict_types=1);

namespace Test\Providers;

use Phalcon\Session\Adapter\Noop;
use RuntimeException;

final class StubSessionHandler extends Noop
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(public readonly array $options = [])
    {
        if (isset($options['fail'])) {
            throw new RuntimeException('fail');
        }
    }
}
