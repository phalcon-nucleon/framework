<?php

namespace Luxury\Constants\Events;

/**
 * Class Loader
 *
 * Contains a list of events related to the area 'loader'
 *
 * @package Luxury\Constants\Events
 */
final class Loader
{
    const BEFORE_CHECK_CLASS = 'loader:beforeCheckClass';
    const PATH_FOUND         = 'loader:pathFound';
    const BEFORE_CHECK_PATH  = 'loader:beforeCheckPath';
    const AFTER_CHECK_CLASS  = 'loader:afterCheckClass';
}
