<?php

namespace Test\Cli\Tasks;

use Test\Stub\StubKernelCli;
use Test\TestCase\TestCase;

/**
 * Class ClearCompiledTaskTest
 *
 * @package Test\Cli\Tasks
 */
class ClearCompiledTaskTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Force Enable Decoration for windows
        putenv('TERM=xterm');
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        // Force Enable Decoration for windows
        putenv('TERM=');
    }

    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        foreach (glob(BASE_PATH . '/bootstrap/compile/*.php') as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function testTask()
    {
        file_put_contents(BASE_PATH . '/bootstrap/compile/loader.php', '<?php');

        $this->dispatchCli('quark clear-compiled -q');

        $this->assertEquals([], glob(BASE_PATH . '/bootstrap/compile/*.php'));
    }
}
