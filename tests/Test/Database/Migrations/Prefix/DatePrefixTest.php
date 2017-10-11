<?php

namespace Test\Database\Migrations\Prefix;

use Neutrino\Database\Migrations\Prefix\DatePrefix;
use Test\TestCase\TestCase;

/**
 * Class DatePrefixTest
 *
 * @package Test\Database\Migrations
 */
class DatePrefixTest extends TestCase
{
    public function testGetPrefix()
    {
        $this->assertRegExp('/^\d{4}_\d{2}_\d{2}_\d{6}$/', (new DatePrefix())->getPrefix());
    }
}
