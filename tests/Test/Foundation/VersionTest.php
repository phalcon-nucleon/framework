<?php

declare(strict_types=1);

namespace Test\Foundation;

use Neutrino\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testVersion(): void
    {
        $expected = Version::MAJOR . '.' . Version::MINOR . '.' . Version::PATCH
            . (Version::STABILITY === '' ? '' : '-' . Version::STABILITY);

        $this->assertSame($expected, Version::get());
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-[\w.]+)?$/', Version::get());
        $this->assertSame(sprintf('%d%02d%02d', Version::MAJOR, Version::MINOR, Version::PATCH), Version::getId());
    }
}
