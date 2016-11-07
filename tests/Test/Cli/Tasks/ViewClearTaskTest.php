<?php

namespace Test\Cli\Tasks;

use Test\Stub\StubKernelCli;
use Test\TestCase\TestCase;

/**
 * Class ViewClearTaskTest
 *
 * @package Test\Cli\Tasks
 */
class ViewClearTaskTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }


    public function setUp()
    {
        global $config;

        $config['view']['compiled_path'] = __DIR__ . '/../../../storage/views/';

        mkdir(__DIR__ . '/../../../storage/views', 0777, true);

        parent::setUp();
    }

    public function tearDown()
    {
        foreach (glob(__DIR__ . '/../../../storage/views/*.*') as $file) {
            @unlink($file);
        }

        @rmdir(__DIR__ . '/../../../storage/views');
        @rmdir(__DIR__ . '/../../../storage');

        parent::tearDown();
    }

    public function testTask()
    {
        file_put_contents(__DIR__ . '/../../../storage/views/view.php', '<?php');

        $this->dispatchCli('luxury view:clear -q');

        $this->assertEquals([], glob(__DIR__ . '/../../../storage/views/*.*'));
    }
}
