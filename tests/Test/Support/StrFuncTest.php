<?php
namespace Test\Support;

use Neutrino\Support\Str;
use Test\TestCase\TestCase;

/**
 * Class StrTest
 *
 * @package Support
 *
 * @coversDefaultClass \Neutrino\Support\Str
 */
class StrFuncTest extends TestCase
{

    /**
     * Test the Str::words method.
     *
     * @group laravel
     */
    public function testStringCanBeLimitedByWords()
    {
        $this->assertEquals('Taylor...', Str::words('Taylor Otwell', 1));
        $this->assertEquals('Taylor___', Str::words('Taylor Otwell', 1, '___'));
        $this->assertEquals('Taylor Otwell', Str::words('Taylor Otwell', 3));
    }
    public function testStringTrimmedOnlyWhereNecessary()
    {
        $this->assertEquals(' Taylor Otwell ', Str::words(' Taylor Otwell ', 3));
        $this->assertEquals(' Taylor...', Str::words(' Taylor Otwell ', 1));
    }
    public function testStringTitle()
    {
        $this->assertEquals('Jefferson Costella', Str::title('jefferson costella'));
        $this->assertEquals('Jefferson Costella', Str::title('jefFErson coSTella'));
    }
    public function testStringWithoutWordsDoesntProduceError()
    {
        $nbsp = chr(0xC2).chr(0xA0);
        $this->assertEquals(' ', Str::words(' '));
        $this->assertEquals($nbsp, Str::words($nbsp));
    }
    public function testStartsWith()
    {
        $this->assertTrue(Str::startsWith('jason', 'jas'));
        $this->assertTrue(Str::startsWith('jason', 'jason'));
        $this->assertTrue(Str::startsWith('jason', ['jas']));
        $this->assertTrue(Str::startsWith('jason', ['day', 'jas']));
        $this->assertFalse(Str::startsWith('jason', 'day'));
        $this->assertFalse(Str::startsWith('jason', ['day']));
        $this->assertFalse(Str::startsWith('jason', ''));
    }
    public function testEndsWith()
    {
        $this->assertTrue(Str::endsWith('jason', 'on'));
        $this->assertTrue(Str::endsWith('jason', 'jason'));
        $this->assertTrue(Str::endsWith('jason', ['on']));
        $this->assertTrue(Str::endsWith('jason', ['no', 'on']));
        $this->assertFalse(Str::endsWith('jason', 'no'));
        $this->assertFalse(Str::endsWith('jason', ['no']));
        $this->assertFalse(Str::endsWith('jason', ''));
        $this->assertFalse(Str::endsWith('7', ' 7'));
    }

    public function dataStrContains()
    {
        return [
            [true, 'taylor', ['ylo']],
            [true, 'taylor', 'ylo'],
            [true, 'taylor', 'taylor'],
            [true, 'taylor', ['xxx', 'ylo']],
            [false, 'taylor', 'xxx'],
            [false, 'taylor', ['xxx']],
            [false, 'taylor', ''],
        ];
    }

    /**
     * @dataProvider dataStrContains
     */
    public function testStrContains($expected, $str, $search)
    {
        $this->assertEquals($expected, Str::contains($str, $search));
    }

    public function testParseCallback()
    {
        $this->assertEquals(['Class', 'method'], Str::parseCallback('Class@method', 'foo'));
        $this->assertEquals(['Class', 'foo'], Str::parseCallback('Class', 'foo'));
    }
    public function testSlug()
    {
        $this->assertEquals('hello-world', Str::slug('hello world'));
        $this->assertEquals('hello-world', Str::slug('hello-world'));
        $this->assertEquals('hello-world', Str::slug('hello_world'));
        $this->assertEquals('hello_world', Str::slug('hello_world', '_'));
    }
    public function testFinish()
    {
        $this->assertEquals('abbc', Str::finish('ab', 'bc'));
        $this->assertEquals('abbc', Str::finish('abbcbc', 'bc'));
        $this->assertEquals('abcbbc', Str::finish('abcbbcbc', 'bc'));
    }
    public function testIs()
    {
        $this->assertTrue(Str::is('/', '/'));
        $this->assertFalse(Str::is('/', ' /'));
        $this->assertFalse(Str::is('/', '/a'));
        $this->assertTrue(Str::is('foo/*', 'foo/bar/baz'));
        $this->assertTrue(Str::is('*/foo', 'blah/baz/foo'));
        $valueObject = new StringableObjectStub('foo/bar/baz');
        $patternObject = new StringableObjectStub('foo/*');
        $this->assertTrue(Str::is('foo/bar/baz', $valueObject));
        $this->assertTrue(Str::is($patternObject, $valueObject));
    }
    public function testLower()
    {
        $this->assertEquals('foo bar baz', Str::lower('FOO BAR BAZ'));
        $this->assertEquals('foo bar baz', Str::lower('fOo Bar bAz'));
    }
    public function testUpper()
    {
        $this->assertEquals('FOO BAR BAZ', Str::upper('foo bar baz'));
        $this->assertEquals('FOO BAR BAZ', Str::upper('foO bAr BaZ'));
    }
    public function testLimit()
    {
        $this->assertEquals('Laravel is...', Str::limit('Laravel is a free, open source PHP web application framework.', 10));
        $this->assertEquals('Laravel is awesome', Str::limit('Laravel is awesome', 20));
        $this->assertEquals('这是一...', Str::limit('这是一段中文', 6));
    }
    public function testLength()
    {
        $this->assertEquals(11, Str::length('foo bar baz'));
    }
    public function testQuickRandom()
    {
        $randomInteger = mt_rand(1, 100);
        $this->assertEquals($randomInteger, strlen(Str::quickRandom($randomInteger)));
        $this->assertInternalType('string', Str::quickRandom());
        $this->assertEquals(16, strlen(Str::quickRandom()));
    }
    public function testRandom()
    {
        $this->assertEquals(16, strlen(Str::random()));
        $randomInteger = mt_rand(1, 100);
        $this->assertEquals($randomInteger, strlen(Str::random($randomInteger)));
        $this->assertInternalType('string', Str::random());
    }
    public function testReplaceFirst()
    {
        $this->assertEquals('fooqux foobar', Str::replaceFirst('bar', 'qux', 'foobar foobar'));
        $this->assertEquals('foo/qux? foo/bar?', Str::replaceFirst('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertEquals('foo foobar', Str::replaceFirst('bar', '', 'foobar foobar'));
        $this->assertEquals('foobar foobar', Str::replaceFirst('xxx', 'yyy', 'foobar foobar'));
    }
    public function testReplaceLast()
    {
        $this->assertEquals('foobar fooqux', Str::replaceLast('bar', 'qux', 'foobar foobar'));
        $this->assertEquals('foo/bar? foo/qux?', Str::replaceLast('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertEquals('foobar foo', Str::replaceLast('bar', '', 'foobar foobar'));
        $this->assertEquals('foobar foobar', Str::replaceLast('xxx', 'yyy', 'foobar foobar'));
    }
    public function testSnake()
    {
        $this->assertEquals('laravel_p_h_p_framework', Str::snake('LaravelPHPFramework'));
        $this->assertEquals('laravel_php_framework', Str::snake('LaravelPhpFramework'));
        $this->assertEquals('laravel php framework', Str::snake('LaravelPhpFramework', ' '));
        $this->assertEquals('laravel_php_framework', Str::snake('Laravel Php Framework'));
        $this->assertEquals('laravel_php_framework', Str::snake('Laravel    Php      Framework   '));
        $this->assertEquals('laravel_php_framework', Str::snake('Laravel    Php      Framework   '));
        // ensure cache keys don't overlap
        $this->assertEquals('laravel__php__framework', Str::snake('LaravelPhpFramework', '__'));
        $this->assertEquals('laravel_php_framework_', Str::snake('LaravelPhpFramework_', '_'));
    }
    public function testStudly()
    {
        $this->assertEquals('LaravelPHPFramework', Str::studly('laravel_p_h_p_framework'));
        $this->assertEquals('LaravelPHPFramework', Str::studly('laravel_p_h_p_framework'));
        $this->assertEquals('LaravelPhpFramework', Str::studly('laravel_php_framework'));
        $this->assertEquals('LaravelPhPFramework', Str::studly('laravel-phP-framework'));
        $this->assertEquals('LaravelPhpFramework', Str::studly('laravel  -_-  php   -_-   framework   '));
    }
    public function testCamel()
    {
        $this->assertEquals('laravelPHPFramework', Str::camel('Laravel_p_h_p_framework'));
        $this->assertEquals('laravelPHPFramework', Str::camel('Laravel_p_h_p_framework'));
        $this->assertEquals('laravelPhpFramework', Str::camel('Laravel_php_framework'));
        $this->assertEquals('laravelPhPFramework', Str::camel('Laravel-phP-framework'));
        $this->assertEquals('laravelPhpFramework', Str::camel('Laravel  -_-  php   -_-   framework   '));
    }
    public function testCapitalize()
    {
        $this->assertEquals('Laravel Framework', Str::capitalize('LARAVEL framework'));
        $this->assertEquals('Laravel Framework', Str::capitalize('LARAVEL framework'));
        $this->assertEquals('Laravel', Str::capitalize('laravel'));
        $this->assertEquals('Laravel', Str::capitalize('lArAVeL'));
    }
    public function testSubstr()
    {
        $this->assertEquals('Ё', Str::substr('БГДЖИЛЁ', -1));
        $this->assertEquals('ЛЁ', Str::substr('БГДЖИЛЁ', -2));
        $this->assertEquals('И', Str::substr('БГДЖИЛЁ', -3, 1));
        $this->assertEquals('ДЖИЛ', Str::substr('БГДЖИЛЁ', 2, -1));
        $this->assertEmpty(Str::substr('БГДЖИЛЁ', 4, -4));
        $this->assertEquals('ИЛ', Str::substr('БГДЖИЛЁ', -3, -1));
        $this->assertEquals('ГДЖИЛЁ', Str::substr('БГДЖИЛЁ', 1));
        $this->assertEquals('ГДЖ', Str::substr('БГДЖИЛЁ', 1, 3));
        $this->assertEquals('БГДЖ', Str::substr('БГДЖИЛЁ', 0, 4));
        $this->assertEquals('Ё', Str::substr('БГДЖИЛЁ', -1, 1));
        $this->assertEmpty(Str::substr('Б', 2));
    }
    public function testUcfirst()
    {
        $this->assertEquals('Laravel', Str::ucfirst('laravel'));
        $this->assertEquals('Laravel framework', Str::ucfirst('laravel framework'));
        $this->assertEquals('Мама', Str::ucfirst('мама'));
        $this->assertEquals('Мама мыла раму', Str::ucfirst('мама мыла раму'));
    }

    public function dataLevenshtein()
    {
        return [
            [['abc', 'bcd', 'xyz'], 'abc', ['bcd', 'xyz', 'abc'], SORT_ASC],
            [['xyz', 'bcd', 'abc'], 'abc', ['bcd', 'xyz', 'abc'], SORT_DESC],
        ];
    }

    /**
     * @dataProvider dataLevenshtein
     *
     * @param $expected
     * @param $word
     * @param $words
     * @param $sort
     */
    public function testLevenshtein($expected, $word, $words, $sort)
    {
        $this->assertEquals($expected, array_keys(Str::levenshtein($word, $words, $sort)));
    }

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
        $error = [];
        set_error_handler(function ($errno, $errstr) use (&$error) {
            $error[] = [
                'no'  => $errno,
                'str' => $errstr
            ];
        });

        $this->assertEquals($expected, Str::normalizePath($path));

        restore_error_handler();

        $this->assertCount(1, $error);
        $this->assertEquals(E_USER_DEPRECATED, $error[0]['no']);
        $this->assertEquals('Deprecated: Neutrino\Support\Str::normalizePath. Use Neutrino\Support\Path::normalize instead.', $error[0]['str']);
    }

}

class StringableObjectStub
{
    private $value;
    public function __construct($value)
    {
        $this->value = $value;
    }
    public function __toString()
    {
        return $this->value;
    }
}
