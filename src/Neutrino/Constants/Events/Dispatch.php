<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events;

/**
 * Class Dispatch
 *
 * Contains a list of events related to the area 'dispatch'
 *
 *  @package Neutrino\Constants\Events
 */
final class Dispatch
{
    public const string BEFORE_DISPATCH_LOOP    = 'dispatch:beforeDispatchLoop';
    public const string BEFORE_DISPATCH         = 'dispatch:beforeDispatch';
    public const string BEFORE_NOT_FOUND_ACTION = 'dispatch:beforeNotFoundAction';
    public const string BEFORE_EXECUTE_ROUTE    = 'dispatch:beforeExecuteRoute';
    public const string AFTER_INITIALIZE        = 'dispatch:afterInitialize';
    public const string AFTER_EXECUTE_ROUTE     = 'dispatch:afterExecuteRoute';
    public const string AFTER_DISPATCH          = 'dispatch:afterDispatch';
    public const string AFTER_DISPATCH_LOOP     = 'dispatch:afterDispatchLoop';
    public const string BEFORE_EXCEPTION        = 'dispatch:beforeException';
    public const string BEFORE_FORWARD          = 'dispatch:beforeForward';
    public const string AFTER_BINDING           = 'dispatch:afterBinding';
    public const string BEFORE_CALL_ACTION      = 'dispatch:beforeCallAction';
    public const string AFTER_CALL_ACTION       = 'dispatch:afterCallAction';
}
