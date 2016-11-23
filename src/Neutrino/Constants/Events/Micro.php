<?php

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
    const BEFORE_HANDLE_ROUTE  = 'micro:beforeHandleRoute';
    const BEFORE_EXECUTE_ROUTE = 'micro:beforeExecuteRoute';
    const AFTER_EXECUTE_ROUTE  = 'micro:afterExecuteRoute';
    const BEFORE_NOT_FOUND     = 'micro:beforeNotFound';
    const AFTER_HANDLE_ROUTE   = 'micro:afterHandleRoute';
}
