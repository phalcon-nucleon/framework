<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events;

/**
 * Class Loader
 *
 * Contains a list of events related to the area 'loader'
 *
 *  @package Neutrino\Constants\Events
 */
final class Loader
{
    public const string BEFORE_CHECK_CLASS = 'loader:beforeCheckClass';
    public const string PATH_FOUND         = 'loader:pathFound';
    public const string BEFORE_CHECK_PATH  = 'loader:beforeCheckPath';
    public const string AFTER_CHECK_CLASS  = 'loader:afterCheckClass';
}
