<?php

namespace Test\Stub;

use Neutrino\Cli\Task;

class StubTask extends Task
{
    public static $enableConstructor = true;

    public function onConstruct()
    {
        if (self::$enableConstructor) {
            parent::onConstruct();
        }
    }

    /**
     * @description StubTask::mainAction
     *
     * @argument abc : abc Arg
     * @argument xyz : xyz Arg
     *
     * @option -o1, --opt_1 : Option one
     * @option -o2, --opt_2 : Option two
     */
    public function mainAction()
    {
    }

    /**
     * StubTask::testAction
     */
    public function testAction(){

    }
}