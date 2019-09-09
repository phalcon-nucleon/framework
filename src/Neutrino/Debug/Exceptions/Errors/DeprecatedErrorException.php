<?php

namespace Neutrino\Debug\Exceptions\Errors;

use ErrorException;

class DeprecatedErrorException extends ErrorException implements InternalErrorException
{
    const TYPES = [
        E_DEPRECATED => true,
        E_USER_DEPRECATED => true,
    ];
}
