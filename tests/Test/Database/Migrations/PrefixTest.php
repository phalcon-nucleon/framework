<?php

declare(strict_types=1);

namespace Test\Database\Migrations;

use Neutrino\Database\Migrations\Prefix\DatePrefix;
use Neutrino\Database\Migrations\Prefix\TimestampPrefix;
use PHPUnit\Framework\TestCase;

final class PrefixTest extends TestCase
{
    public function testDatePrefix(): void
    {
        $this->assertMatchesRegularExpression('/^\d{4}_\d{2}_\d{2}_\d{6}$/', (new DatePrefix())->getPrefix());
        $this->assertSame('my_class_name', (new DatePrefix())->deletePrefix('2017_21_11_225604_my_class_name'));
    }

    public function testTimestampPrefix(): void
    {
        $this->assertMatchesRegularExpression('/^\d{10,}$/', (new TimestampPrefix())->getPrefix());
        $this->assertSame('my_class_name', (new TimestampPrefix())->deletePrefix('1511357112_my_class_name'));
        $this->assertSame('my-class', (new TimestampPrefix())->deletePrefix('1511357112-my-class', '-'));
    }
}
