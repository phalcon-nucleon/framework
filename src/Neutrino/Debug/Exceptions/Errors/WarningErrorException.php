<?php

namespace Neutrino\Debug\Exceptions\Errors;

use ErrorException;

class WarningErrorException extends ErrorException implements InternalErrorException
{
    const TYPES = [
        E_WARNING => true,
        E_USER_WARNING => true,
        E_CORE_WARNING => true,
        E_COMPILE_WARNING => true,
    ];
}
