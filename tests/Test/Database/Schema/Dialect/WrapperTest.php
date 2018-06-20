<?php

namespace Test\Database\Schema\Dialect;

use Neutrino\Database\Schema\Dialect\Wrapper;
use Phalcon\Db\DialectInterface;
use Test\TestCase\TestCase;

class WrapperTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testEnableForeignKeyConstraints()
    {
        (new Wrapper($this->createMock(DialectInterface::class)))->enableForeignKeyConstraints();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDisableForeignKeyConstraints()
    {
        (new Wrapper($this->createMock(DialectInterface::class)))->disableForeignKeyConstraints();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRenameTable()
    {
        (new Wrapper($this->createMock(DialectInterface::class)))->renameTable('old_table', 'new_table');
    }
}
