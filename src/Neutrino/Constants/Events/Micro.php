<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events;

/**
 * Class Micro
 *
 * Contains a list of events related to the area 'micro'
 *
 *  @package Neutrino\Constants\Events
 */
final class Micro
{
    public const string BEFORE_HANDLE_ROUTE  = 'micro:beforeHandleRoute';
    public const string BEFORE_EXECUTE_ROUTE = 'micro:beforeExecuteRoute';
    public const string AFTER_EXECUTE_ROUTE  = 'micro:afterExecuteRoute';
    public const string BEFORE_NOT_FOUND     = 'micro:beforeNotFound';
    public const string AFTER_HANDLE_ROUTE   = 'micro:afterHandleRoute';
    public const string AFTER_BINDING        = 'micro:afterBinding';
    public const string BEFORE_EXCEPTION     = 'micro:beforeException';
}
