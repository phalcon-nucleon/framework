<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 26/07/2017
 * Time: 16:34
 */

namespace Test\Support;

use Neutrino\Support\Func;

class FuncTest extends \PHPUnit_Framework_TestCase
{
    public function testTap()
    {
        $object = (object) ['id' => 1];
        $this->assertEquals(2, Func::tap($object, function ($object) {
            $object->id = 2;
        })->id);
    }
}
