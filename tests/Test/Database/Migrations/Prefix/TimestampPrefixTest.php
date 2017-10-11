<?php

namespace Test\Database\Migrations\Prefix;

use Neutrino\Database\Migrations\Prefix\TimestampPrefix;
use Test\TestCase\TestCase;

/**
 * Class TimestampPrefixTest
 *
 * @package Test\Database\Migrations\Prefix
 */
class TimestampPrefixTest extends TestCase
{
    public function testGetPrefix()
    {
        $this->assertRegExp('/^\d{10}$/', (new TimestampPrefix())->getPrefix());
    }
}
