<?php

namespace Test\Preloader;

use Neutrino\PhpPreloader\Factory;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;
use PHPUnit\Framework\TestCase;

class PreloaderTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();

        @unlink(__DIR__ . '/stub/newfile.php');
    }

    public function testGetter()
    {
        $preloader = (new Factory())->create();

        $this->assertInstanceOf(Parser::class, $preloader->getParser());
        $this->assertInstanceOf(NodeTraverserInterface::class, $preloader->getTraverser());
        $this->assertInstanceOf(PrettyPrinterAbstract::class, $preloader->getPrinter());
    }

    public function testPrepareOutputStrict()
    {
        $preloader = (new Factory())->create();

        if (PHP_VERSION_ID < 70000) {
            try {
                $preloader->prepareOutput(__DIR__ . '/stub/newfile.php', true);
            } catch (\RuntimeException $e) {
            }

            $this->assertTrue(isset($e));
            $this->assertInstanceOf(\RuntimeException::class, $e);
            $this->assertEquals('Strict mode requires PHP 7 or greater.', $e->getMessage());

            return;
        }

        $output = $preloader->prepareOutput(__DIR__ . '/stub/newfile.php', true);

        fclose($output);

        $this->assertEquals("<?php  declare(strict_types=1);\n", file_get_contents(__DIR__ . '/stub/newfile.php'));
    }

    public function testParseCode()
    {
        $preloader = (new Factory())->create();

        $output = $preloader->prepareOutput(__DIR__ . '/stub/newfile.php');

        fwrite($output, $preloader->getCode(__DIR__ . '/stub/stub.php'));

        fclose($output);

        $expected = <<<PHP
<?php
namespace StubNamespace;

class Foo extends \Foo\Bar
{
    public function bar()
    {
        \$a = [];
        return \$a;
    }
}
PHP;

        $this->assertEquals($expected, file_get_contents(__DIR__ . '/stub/newfile.php'));
    }
}
