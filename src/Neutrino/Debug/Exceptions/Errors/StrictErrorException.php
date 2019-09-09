<?php

namespace Neutrino\Debug\Exceptions\Errors;

use ErrorException;

class StrictErrorException extends ErrorException implements InternalErrorException
{
    const TYPES = [
        E_STRICT => true,
    ];
}
