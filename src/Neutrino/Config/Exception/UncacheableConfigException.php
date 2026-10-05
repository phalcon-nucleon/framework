<?php

declare(strict_types=1);

namespace Neutrino\Config\Exception;

use RuntimeException;

final class UncacheableConfigException extends RuntimeException
{
    public function __construct(
        public readonly string $configFile,
        public readonly string $key,
        public readonly string $type,
    ) {
        parent::__construct(sprintf(
            'The configuration cannot be cached: "%s" holds a value of type %s at key "%s". '
            . 'Only scalars, arrays, null and enums can be cached.',
            $configFile,
            $type,
            $key,
        ));
    }
}
