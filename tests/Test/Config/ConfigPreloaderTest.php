<?php

namespace Test\Config;

use Neutrino\Config\ConfigPreloader;
use Neutrino\PhpPreloader\Exceptions\DirConstantException;
use Neutrino\PhpPreloader\Exceptions\FileConstantException;
use PHPUnit\Framework\TestCase;

class ConfigPreloaderTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();

        @unlink(BASE_PATH . '/bootstrap/compile/config.php');
        @unlink(BASE_PATH . '/config/test.php');
        @rmdir(BASE_PATH . '/config');
    }

    public function testConfigPreloader()
    {
        mkdir(BASE_PATH . '/config');
        file_put_contents(BASE_PATH . '/config/test.php', <<<PHP
<?php

\$bar = 'bar';

return [
    'foo' => \$bar
];
PHP
        );
        $preloader = new ConfigPreloader;

        $preloader->compile();

        $this->assertFileExists(BASE_PATH . '/bootstrap/compile/config.php');
        $this->assertEquals(<<<PHP
<?php
\$config = [];
\$bar = 'bar';
\$config['test'] = ['foo' => \$bar];
return \$config;
PHP
            , file_get_contents(BASE_PATH . '/bootstrap/compile/config.php'));
    }

    public function testConfigPreloaderThrowDirConstException()
    {
        $this->assertThrowExceptionWhenTypeConstUsed(DirConstantException::class, 'dir');
    }
    public function testConfigPreloaderThrowFileConstException()
    {
        $this->assertThrowExceptionWhenTypeConstUsed(FileConstantException::class, 'file');
    }

    protected function assertThrowExceptionWhenTypeConstUsed($exceptionClass, $type)
    {

        $const = '__' . strtoupper($type) . '__';

        file_put_contents(BASE_PATH . '/bootstrap/compile/config.php', '');

        mkdir(BASE_PATH . '/config');
        file_put_contents(BASE_PATH . '/config/test.php', <<<PHP
<?php

return [
    'foo' => $const
];
PHP
        );
        $preloader = new ConfigPreloader;

        try {
            $preloader->compile();
        } catch (\Exception $e) {
        }

        $this->assertTrue(isset($e));
        $this->assertInstanceOf($exceptionClass, $e);
        $this->assertEquals("Usage of $const constant is prohibited. Use BASE_PATH . '/path_to_$type' instead.\nin : " . BASE_PATH . "/config/test.php", $e->getMessage());

        $this->assertFileNotExists(BASE_PATH . ' / bootstrap / compile / config . php');
    }
}
