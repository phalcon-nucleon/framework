<?php

namespace Test\Cli\Tasks;

use Fake\Kernels\Cli\StubKernelCli;
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
        $this->setConfig(['view' => ['compiled_path' => BASE_PATH . '/storage/views/']]);

        parent::setUp();
    }

    public function tearDown()
    {
        foreach (glob(BASE_PATH . '/storage/views/*.*') as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function testTask()
    {
        file_put_contents(BASE_PATH . '/storage/views/view.php', '<?php');

        $this->dispatchCli('quark view:clear -q');

        $this->assertEquals([], glob(__DIR__ . '/../../../storage/views/*.*'));
    }
}
