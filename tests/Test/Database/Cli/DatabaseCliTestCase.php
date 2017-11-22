<?php

namespace Test\Database\Cli;

use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Database\Migrations\MigrationCreator;
use Neutrino\Database\Migrations\Migrator;
use Neutrino\Database\Migrations\Storage\StorageInterface;
use Phalcon\Config;
use Test\TestCase\TestCase;

/**
 * Class DatabaseCliTestCase
 *
 * @package     Test\Database\Cli
 */
class DatabaseCliTestCase extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $migrator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $creator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $output;

    public function setUp()
    {
        parent::setUp();

        $this->getDI()->set(Services::CONFIG, new Config([
            'migrations' => [
                'path' => BASE_PATH . '/migrations'
            ]
        ]));

        $this->output   = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);
        $this->creator  = $this->mockService(MigrationCreator::class, MigrationCreator::class, true);
        $this->migrator = $this->mockService(Migrator::class, Migrator::class, true);
        $this->storage  = $this->mockService(StorageInterface::class, StorageInterface::class, true);

        $this->migrator->method('getStorage')->willReturn($this->storage);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->creator  = null;
        $this->migrator = null;
        $this->output   = null;
        $this->storage  = null;
    }
}
