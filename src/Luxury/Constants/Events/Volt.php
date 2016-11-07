<?php

namespace Luxury\Constants\Events;

/**
 * Class Volt
 *
 * Contains a list of events related to the area 'volt'
 *
 * @package Luxury\Constants\Events
 */
final class Volt
{
    const COMPILE_FUNCTION   = 'volt:compileFunction';
    const COMPILE_FILTER     = 'volt:compileFilter';
    const RESOLVE_EXPRESSION = 'volt:resolveExpression';
    const COMPILE_STATEMENT  = 'volt:compileStatement';
}
