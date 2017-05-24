<?php

namespace Test\Dotconst;

use Neutrino\Dotconst\Compile;
use Neutrino\Dotconst\Exception\InvalidFileException;
use Neutrino\Dotconst\Loader;
use PHPUnit\Framework\TestCase;

class DotconstTest extends TestCase
{
    public static $app_dir = __DIR__ . '/../../.fake/nucleon_app';

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
        $reflecion = new \ReflectionClass(Loader::class);
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
            [['directory' => 'directory/testing'], ['directory' => '@php/dir@/testing']],
            [['directory' => 'directory' . DIRECTORY_SEPARATOR . 'testing'], ['directory' => '@php/dir:/testing@']],
            [['directory' => 'directory' . DIRECTORY_SEPARATOR . 'testing/sub'], ['directory' => '@php/dir:/testing@/sub']],
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

    /**
     * @expectedException \Neutrino\Dotconst\Exception\InvalidFileException
     */
    public function testWrongFile()
    {
        try {
            $triggedError = [];

            set_error_handler(function () use (&$triggedError) {
                $triggedError[] = var_export(func_get_args(), true);
            });

            Loader::fromFiles(self::$app_dir . '/.wrong');
        } catch (InvalidFileException $e) {
            $this->assertCount(1, $triggedError);

            throw $e;
        } finally {
            restore_error_handler();
        }
    }

    public function testFromFilesNoFile()
    {
        $config = Loader::fromFiles('no file');

        $this->assertEquals([], $config);
    }

    /**
     * depends testNormalizePath
     * depends testDynamize
     */
    public function testFromFiles()
    {
        $config = Loader::fromFiles(self::$app_dir );

        $this->assertEquals([
            'BASE_PATH'       => realpath(dirname(self::$app_dir .'/.const.ini')),
            'PUBLIC_PATH'     => realpath(dirname(self::$app_dir .'/.const.ini')) . DIRECTORY_SEPARATOR . 'public',
            'STORAGE_PATH'    => realpath(dirname(self::$app_dir .'/.const.ini')) . DIRECTORY_SEPARATOR . 'storage',
            'OVERRIDE_PATH'   => realpath(dirname(self::$app_dir .'/.const.ini')) . DIRECTORY_SEPARATOR . 'fake_dir',
            'NESTED'          => realpath(dirname(self::$app_dir .'/.const.ini')) . DIRECTORY_SEPARATOR . 'storage',
            'NESTEDSUB'       => realpath(dirname(self::$app_dir .'/.const.ini')) . DIRECTORY_SEPARATOR . 'storage/test',
            'APP_ENV'         => 'testing',
            'TEST_INT'        => 123,
            'TEST_FLOAT'      => 123.123,
            'TEST_BOOL'       => false,
            'TEST_STR'        => 'abc',
            'TEST_ARR_V1'     => 'v1',
            'TEST_ARR_V2'     => 'v2',
            'OVERRIDE_INT'    => 456,
            'OVERRIDE_FLOAT'  => 987.5,
            'OVERRIDE_BOOL'   => true,
            'OVERRIDE_STR'    => 'override',
            'OVERRIDE_ARR_V1' => 'over1',
            'OVERRIDE_ARR_V2' => 'over2',
            'OVERRIDE_ARR_V3' => 'over3',
        ], $config);
    }

    /**
     * @runInSeparateProcess
     *
     * @depends testFromFiles
     */
    public function testLoad()
    {
        Loader::load(self::$app_dir);

        $this->assertPredefineConstant();
    }

    /**
     * @runInSeparateProcess
     *
     * @expectedException \Neutrino\Dotconst\Exception\InvalidFileException
     */
    public function testCompileWrongPath()
    {
        try {
            $triggedError = [];

            set_error_handler(function () use (&$triggedError) {
                $triggedError[] = var_export(func_get_args(), true);
            });

            $compilePath = 'wrong path';
            Compile::compile(self::$app_dir, $compilePath);
        } catch (InvalidFileException $e) {
            $this->assertCount(1, $triggedError);

            throw $e;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @runInSeparateProcess
     *
     * @depends testFromFiles
     */
    public function testCompile()
    {
        $appPath = __DIR__ . '/../.app_fake';
        $compilePath = $appPath . '/bootstrap/compile';
        $compileFile = $compilePath . '/consts.php';

        Compile::compile($appPath, $compilePath);

        $this->assertFileExists($compileFile);

        $content = file_get_contents($compileFile);

        $this->assertEquals(implode(PHP_EOL, [
            '<?php',
            "define('BASE_PATH', " . var_export(realpath(dirname(self::$app_dir . '/.const.ini')), true) . ");",
            "define('PUBLIC_PATH', " . var_export(realpath(dirname(self::$app_dir . '/.const.ini')) . DIRECTORY_SEPARATOR . 'public', true) . ");",
            "define('STORAGE_PATH', " . var_export(realpath(dirname(self::$app_dir . '/.const.ini')) . DIRECTORY_SEPARATOR . 'storage', true) . ");",
            "define('OVERRIDE_PATH', " . var_export(realpath(dirname(self::$app_dir . '/.const.ini')) . DIRECTORY_SEPARATOR . 'fake_dir', true) . ");",
            "define('NESTED', " . var_export(realpath(dirname(self::$app_dir . '/.const.ini')) . DIRECTORY_SEPARATOR . 'storage', true) . ");",
            "define('NESTEDSUB', " . var_export(realpath(dirname(self::$app_dir . '/.const.ini')) . DIRECTORY_SEPARATOR . 'storage/test', true) . ");",
            "define('APP_ENV', 'testing');",
            "define('TEST_INT', 123);",
            "define('TEST_FLOAT', 123.123);",
            "define('TEST_BOOL', false);",
            "define('TEST_STR', 'abc');",
            "define('TEST_ARR_V1', 'v1');",
            "define('TEST_ARR_V2', 'v2');",
            "define('OVERRIDE_INT', 456);",
            "define('OVERRIDE_FLOAT', 987.5);",
            "define('OVERRIDE_BOOL', true);",
            "define('OVERRIDE_STR', 'override');",
            "define('OVERRIDE_ARR_V1', 'over1');",
            "define('OVERRIDE_ARR_V2', 'over2');",
            "define('OVERRIDE_ARR_V3', 'over3');",
            "",
        ]), $content);
    }

    /**
     * @runInSeparateProcess
     *
     * @depends testLoad
     * @depends testCompile
     */
    public function testRunWithoutCompile()
    {
        Loader::load(self::$app_dir, 'no file');

        $this->assertPredefineConstant();
    }

    /**
     * @runInSeparateProcess
     *
     * @depends testLoad
     * @depends testCompile
     */
    public function testRunWithCompile()
    {
        $compilePath = self::$app_dir . '/bootstrap/compile';

        Compile::compile(self::$app_dir, $compilePath);

        Loader::load(self::$app_dir, $compilePath);

        $this->assertPredefineConstant();
    }

    protected function assertPredefineConstant()
    {
        $this->assertEquals(realpath(dirname(self::$app_dir . '/.const.ini')), BASE_PATH);
        $this->assertEquals(realpath(dirname(self::$app_dir . '/.const.ini')) . DIRECTORY_SEPARATOR . 'public', PUBLIC_PATH);
        $this->assertEquals(realpath(dirname(self::$app_dir . '/.const.ini')) . DIRECTORY_SEPARATOR . 'storage', STORAGE_PATH);
        $this->assertEquals(realpath(dirname(self::$app_dir . '/.const.ini')) . DIRECTORY_SEPARATOR . 'fake_dir', OVERRIDE_PATH);
        $this->assertEquals('testing', APP_ENV);
        $this->assertEquals(123, TEST_INT);
        $this->assertEquals(123.123, TEST_FLOAT);
        $this->assertEquals(false, TEST_BOOL);
        $this->assertEquals('abc', TEST_STR);
        $this->assertEquals('v1', TEST_ARR_V1);
        $this->assertEquals('v2', TEST_ARR_V2);
        $this->assertEquals(456, OVERRIDE_INT);
        $this->assertEquals(987.5, OVERRIDE_FLOAT);
        $this->assertEquals(true, OVERRIDE_BOOL);
        $this->assertEquals('override', OVERRIDE_STR);
        $this->assertEquals('over1', OVERRIDE_ARR_V1);
        $this->assertEquals('over2', OVERRIDE_ARR_V2);
        $this->assertEquals('over3', OVERRIDE_ARR_V3);
    }

    public function tearDown()
    {
        if (file_exists($file = __DIR__ . '/../../.fake/nucleon_app/bootstrap/compile/consts.php')) {
            @unlink($file);
        }

        parent::tearDown();
    }
}
