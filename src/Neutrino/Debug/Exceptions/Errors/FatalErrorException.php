<?php

namespace Neutrino\Debug\Exceptions\Errors;

use ErrorException;

class FatalErrorException extends ErrorException implements InternalErrorException
{
    const TYPES = [
        E_ERROR => true,
        E_PARSE => true,
        E_CORE_ERROR => true,
        E_USER_ERROR => true,
        E_COMPILE_ERROR => true,
        E_RECOVERABLE_ERROR => true,
    ];
}
