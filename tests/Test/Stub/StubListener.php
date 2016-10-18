<?php

namespace Test\Stub;

use Luxury\Constants\Events;
use Luxury\Events\Listener;
use Test\TestCase\TestListenable;
use Test\TestCase\TestListenize;

/**
 * Class StubListener
 *
 * @package     Test\Stub
 */
class StubListener extends Listener implements TestListenable
{
    use TestListenize;

    protected $listen = [
        Events\Application::BOOT                  => 'onBoot',
        Events\Application::BEFORE_HANDLE_REQUEST => 'beforeHandleRequest',
    ];

    protected $space = [
        Events::DISPATCH
    ];

    public static $instance;

    public function __construct()
    {
        parent::__construct();

        self::$instance = $this;
    }

    public function onBoot()
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    public function beforeHandleRequest()
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    public function beforeDispatchLoop()
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    public function beforeDispatch()
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    public function beforeNotFoundAction()
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    public function beforeExecuteRoute()
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    public function afterInitialize()
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    public function afterExecuteRoute()
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    public function afterDispatch()
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    public function afterDispatchLoop()
    {
        $this->view(__FUNCTION__, func_get_args());
    }
}
