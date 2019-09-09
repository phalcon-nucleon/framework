<?php

namespace Neutrino\Debug\Exceptions\Errors;

use ErrorException;

class NoticeErrorException extends ErrorException implements InternalErrorException
{
    const TYPES = [
        E_NOTICE => true,
        E_USER_NOTICE => true,
    ];
}
