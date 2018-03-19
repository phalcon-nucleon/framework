<?php

namespace Test\Dotconst;

use Neutrino\Dotconst;
use Neutrino\Dotconst\Exception\InvalidFileException;
use Neutrino\Dotconst\Loader;
use PHPUnit\Framework\TestCase;

/**
 * Class DotconstTest
 *
 * @package Test\Dotconst
 */
class DotconstTest extends TestCase
{
    public function dataNormalizePath()
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
     * @dataProvider dataNormalizePath
     *
     * @param $expected
     * @param $path
     */
    public function testNormalizePath($expected, $path)
    {
        $reflecion = new \ReflectionClass(Dotconst\Helper::class);
        $method = $reflecion->getMethod('normalizePath');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke(null, $path));
    }

    public function dataDynamize()
    {
        return [
            [['max_int' => PHP_INT_MAX], ['max_int' => '@php/const:PHP_INT_MAX']],
            [['max_int' => PHP_INT_MAX], ['max_int' => '@php/const:PHP_INT_MAX@']],
            [['separator' => DIRECTORY_SEPARATOR], ['separator' => '@php/const:DIRECTORY_SEPARATOR']],
            [['separator' => DIRECTORY_SEPARATOR], ['separator' => '@php/const:DIRECTORY_SEPARATOR@']],
            [['separator' => DIRECTORY_SEPARATOR . '.testing'], ['separator' => '@php/const:DIRECTORY_SEPARATOR@.testing']],

            [['directory' => 'directory'], ['directory' => '@php/dir']],
            [['directory' => 'directory'], ['directory' => '@php/dir@']],
            [['directory' => 'directory' . DIRECTORY_SEPARATOR . 'testing'], ['directory' => '@php/dir@/testing']],
            [['directory' => 'directory' . DIRECTORY_SEPARATOR . 'testing'], ['directory' => '@php/dir:/testing@']],
            [['directory' => 'directory' . DIRECTORY_SEPARATOR . 'testing' . DIRECTORY_SEPARATOR . 'sub'], ['directory' => '@php/dir:/testing@/sub']],

            [['env' => null], ['env' => '@php/env:some_env_value']],
            [['env' => 'test'], ['env' => '@php/env:some_env_value:test']],
        ];
    }

    /**
     * @dataProvider dataDynamize
     *
     * @depends      testNormalizePath
     *
     * @param $expected
     * @param $array
     */
    public function testDynamize($expected, $array)
    {
        $reflecion = new \ReflectionClass(Loader::class);
        $method = $reflecion->getMethod('dynamize');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke(null, $array, 'directory'));
    }

    public function testFromFilesNoFile()
    {
        $config = Loader::fromFiles('no file');

        $this->assertEquals([], $config);
    }

    public function testNestedConstSort()
    {
        $given = [
            'A' => [
                'require' => 'C',
            ],
            'B' => [
                'require' => null,
            ],
            'C' => [
                'require' => 'D',
            ],
            'H' => [
                'require' => 'F',
            ],
            'D' => [
                'require' => '_E_',
            ],
            'F' => [
                'require' => 'A',
            ],
            'G' => [
                'require' => 'A',
            ],
            'I' => [
                'require' => null,
            ],
        ];

        $expected = [
            'B' => [
                'require' => null,
            ],
            'I' => [
                'require' => null,
            ],
            'D' => [
                'require' => '_E_',
            ],
            'C' => [
                'require' => 'D',
            ],
            'A' => [
                'require' => 'C',
            ],
            'F' => [
                'require' => 'A',
            ],
            'G' => [
                'require' => 'A',
            ],
            'H' => [
                'require' => 'F',
            ],
        ];

        $reflecion = new \ReflectionClass(Dotconst\Helper::class);
        $method = $reflecion->getMethod('nestedConstSort');
        $method->setAccessible(true);

        $this->assertEquals(var_export($expected, true), var_export($method->invoke(null, $given), true));
    }

    /**
     * @expectedException \Neutrino\Dotconst\Exception\CycleNestedConstException
     */
    public function testCyclicNestedConstSort()
    {
        $given = [
            'A' => [
                'require' => 'B',
            ],
            'B' => [
                'require' => 'C',
            ],
            'C' => [
                'require' => 'A',
            ],
        ];

        $reflecion = new \ReflectionClass(Dotconst\Helper::class);
        $method = $reflecion->getMethod('nestedConstSort');
        $method->setAccessible(true);
        $method->invoke(null, $given);
    }
}
