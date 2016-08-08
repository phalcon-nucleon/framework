<?php

namespace Middleware;

/**
 * Class Middlewarize
 *
 * @package     Middleware
 */
trait Middlewarize
{
    /**
     * Called on the initialization
     *
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function init($event, $source, $data = null)
    {
        $this->view(__FUNCTION__, [$event, $source, $data]);
    }

    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function before($event, $source, $data = null)
    {
        $this->view(__FUNCTION__, [$event, $source, $data]);
    }

    /**
     * Called after the execution of handler
     *
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function after($event, $source, $data = null)
    {
        $this->view(__FUNCTION__, [$event, $source, $data]);
    }

    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event|mixed $event
     * @param \Phalcon\Dispatcher|mixed   $source
     * @param mixed|null                  $data
     *
     * @throws \Exception
     * @return bool
     */
    public function finish($event, $source, $data = null)
    {
        $this->view(__FUNCTION__, [$event, $source, $data]);
    }
}
