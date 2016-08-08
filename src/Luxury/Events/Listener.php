<?php

namespace Luxury\Events;

use Phalcon\Di\Injectable;
use Phalcon\Events\Event;

/**
 * Class Listener
 *
 * @package Luxury\Events
 */
abstract class Listener extends Injectable
{
    /**
     * List to event to listen.
     *
     * ex : [
     *      {eventName} => {methodCall},
     *      \Luxury\Constants\Events\Application::BOOT => 'onBoot',
     *      \Luxury\Constants\Events\Dispatch::BEFORE_DISPATCH => 'onBeforeDispatch'
     * ]
     *
     * @var string[]
     */
    protected $listen;

    /**
     * List to space to listen.
     *
     * ex : [
     *      {eventSpace},
     *      \Luxury\Constants\Events::APPLICATION,
     *      \Luxury\Constants\Events::DISPATCH,
     * ]
     *
     * @var string[]
     */
    protected $space;

    /**
     * Attach all require event to make the listener
     */
    public function attach()
    {
        $em = $this->getEventsManager();

        if (!empty($this->space)) {
            foreach ($this->space as $space) {
                $em->attach($space, $this);
            }
        }

        if (!empty($this->listen)) {
            foreach ($this->listen as $event => $callback) {
                if (!method_exists($this, $callback)) {
                    throw new \RuntimeException(
                        "Method '$callback' not exist in " . get_class($this)
                    );
                }

                $closure = function (Event $event, $handler, $data = null) use ($callback) {
                    return $this->$callback($event, $handler, $data);
                };

                $em->attach($event, \Closure::bind($closure, $this));
            }
        }
    }
}
