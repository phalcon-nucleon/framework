<?php

namespace Test\Support;

use Neutrino\Support\Path;
use PHPUnit\Framework\TestCase;

class PathFuncTest extends TestCase
{
    public static function dataNormalizePath()
    {
        $s = DIRECTORY_SEPARATOR;

        return [
            ['', ''],
            [$s, '/'],
            [$s . '0', '/0/'],
            [$s . 'home', '/home/'],
            ['home', 'home/'],
            [$s . 'home', '/home/test/..'],
            [$s . 'home', '/home/test/../'],
            [$s . 'home' . $s . 'some', '/home/test/.././some'],
            [$s . 'home' . $s . 'some', '/../home/test/.././some'],
            [$s . 'hello' . $s . '0' . $s . 'you', '/hello/0//how/../are/../you'],
            [$s . 'hello' . $s . '0' . $s . 'are' . $s . 'you', '/ /hello/0// / /how/../are/you/./././'],
            [$s . 'hello' . $s . '0.0' . $s . 'are' . $s . 'you', '/ /hello/0.0/././././////how/../are/you'],
        ];
    }

    /**
     *
     * @param $expected
     * @param $path
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataNormalizePath')]
    public function testNormalizePath($expected, $path)
    {
        $this->assertEquals($expected, Path::normalize($path));
    }

    public static function dataFindRelative()
    {
        return [
            ['dir', '/app', '/app/dir'],
            ['dir', '/app/', '/app/dir'],
            ['dir', '/app', '/app/dir/'],
            ['dir', '/app/', '/app/dir/'],
            ['..', '/app/dir', '/app'],
            ['..', '/app/dir/', '/app'],
            ['..', '/app/dir', '/app/'],
            ['..', '/app/dir/', '/app/'],
            ['../..', '/app/dir/sub', '/app'],
            ['../../../foo/bar', '/app/dir/sub/sub', '/app/foo/bar'],
        ];
    }

    /**
     *
     * @param $expected
     * @param $from
     * @param $to
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataFindRelative')]
    public function testFindRelative($expected, $from, $to)
    {
        $this->assertEquals($expected, Path::findRelative($from, $to));
    }

}
