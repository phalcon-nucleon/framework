<?php

namespace Test\Database\Cli;

use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;

/**
 * Class MakerTaskTest
 *
 * @package Test\Database\Cli
 */
class MakerTaskTest extends DatabaseCliTestCase
{

    public function testMainActionNoOptions()
    {
        $this->creator
            ->expects($this->once())
            ->method('create')
            ->with('test', BASE_PATH.'/migrations', null, null);

        $this->dispatchCli('quark make:migration test');
    }

    public function testMainActionCreate()
    {
        $this->creator
            ->expects($this->once())
            ->method('create')
            ->with('test', BASE_PATH.'/migrations', null, true);

        $this->dispatchCli('quark make:migration test --create');
    }

    public function testMainActionTable()
    {
        $this->creator
            ->expects($this->once())
            ->method('create')
            ->with('test', BASE_PATH.'/migrations', 'my_table', null);

        $this->dispatchCli('quark make:migration test --table=my_table');
    }

    public function testMainActionCreateTable()
    {
        $this->creator
            ->expects($this->once())
            ->method('create')
            ->with('test', BASE_PATH.'/migrations', 'my_table', true);

        $this->dispatchCli('quark make:migration test --create --table=my_table');
    }

    public function testMainActionCreateByName()
    {
        $this->creator
            ->expects($this->once())
            ->method('create')
            ->with('create_my_table_table', BASE_PATH.'/migrations', 'my_table', true);

        $this->dispatchCli('quark make:migration create_my_table_table');
    }

}
