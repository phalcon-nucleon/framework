<?php

namespace Test\Stub;

use Luxury\Constants\Events\Application as AppEvent;
use Luxury\Foundation\Middleware\Application as ApplicationMiddleware;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Interfaces\Middleware\FinishInterface;
use Luxury\Interfaces\Middleware\InitInterface;
use Phalcon\Events\Event;
use Test\TestCase\TestListenable;
use Test\TestCase\TestListenize;

/**
 * Class StubMiddleware
 *
 * @package     Test\Stub
 */
class StubMiddleware extends ApplicationMiddleware implements InitInterface, BeforeInterface, AfterInterface, FinishInterface, TestListenable
{
    use TestListenize;

    public static $instance;

    public function __construct()
    {
        parent::__construct();

        if ($this instanceof InitInterface) {
            $this->listen[AppEvent::BOOT] = 'init';
        }
        if ($this instanceof FinishInterface) {
            $this->listen[AppEvent::BEFORE_SEND_RESPONSE] = 'finish';
        }
        $this->listen[AppEvent::VIEW_RENDER] = 'viewRender';
        self::$instance                      = $this;
    }

    /**
     * Called on the initialization
     *
     * @param \Phalcon\Events\Event     $event
     * @param \Phalcon\Dispatcher|mixed $source
     * @param mixed|null                $data
     *
     * @throws \Exception
     * @return bool
     */
    public function init(Event $event, $source, $data = null)
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event     $event
     * @param \Phalcon\Dispatcher|mixed $source
     * @param mixed|null                $data
     *
     * @throws \Exception
     * @return bool
     */
    public function before(Event $event, $source, $data = null)
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    /**
     * Called after the execution of handler
     *
     * @param \Phalcon\Events\Event     $event
     * @param \Phalcon\Dispatcher|mixed $source
     * @param mixed|null                $data
     *
     * @throws \Exception
     * @return bool
     */
    public function after(Event $event, $source, $data = null)
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event     $event
     * @param \Phalcon\Dispatcher|mixed $source
     * @param mixed|null                $data
     *
     * @throws \Exception
     * @return bool
     */
    public function finish(Event $event, $source, $data = null)
    {
        $this->view(__FUNCTION__, func_get_args());
    }

    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event     $event
     * @param \Phalcon\Dispatcher|mixed $source
     * @param mixed|null                $data
     *
     * @throws \Exception
     * @return bool
     */
    public function viewRender(Event $event, $source, $data = null)
    {
        $this->view(__FUNCTION__, func_get_args());
    }
}
