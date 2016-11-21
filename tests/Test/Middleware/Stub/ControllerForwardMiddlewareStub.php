<?php

namespace Test\Middleware\Stub;

use Luxury\Foundation\Middleware\Controller;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Interfaces\Middleware\FinishInterface;
use Phalcon\Events\Event;
use Test\Middleware\Middlewarize;
use Test\TestCase\TestListenable;
use Test\TestCase\TestListenize;

/**
 * Class ControllerForwardMiddlewareStub
 *
 * @package     Test\Middleware\Stub
 */
class ControllerForwardMiddlewareStub extends Controller implements
    TestListenable,
    BeforeInterface,
    AfterInterface,
    FinishInterface
{
    use Middlewarize {
        before as __before;
    }
    use TestListenize;

    public $forwardClass;

    public $forwardAction;

    public function __construct($controllerClass, $forwardClass, $forwardAction)
    {
        parent::__construct($controllerClass);

        $this->forwardClass  = $forwardClass;
        $this->forwardAction = $forwardAction;
    }

    public function before(Event $event, $source, $data = null)
    {
        $this->__before($event, $source, $data);

        $this->dispatcher->forward([
            'controller' => $this->forwardClass,
            'action'     => $this->forwardAction
        ]);
    }
}