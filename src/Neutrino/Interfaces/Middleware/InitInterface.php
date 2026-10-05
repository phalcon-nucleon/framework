<?php

declare(strict_types=1);

namespace Neutrino\Interfaces\Middleware;

use Phalcon\Events\Event;

interface InitInterface
{
    /**
     * Called on the initialization (application boot, or start of the dispatch loop).
     *
     * No return type is imposed, so that implementations may return nothing:
     * only `false` has an effect (it stops cancelable events).
     *
     * @param object $source The application, the dispatcher…
     * @param mixed  $data   Data of the event
     *
     * @return bool|null|void
     */
    public function init(Event $event, object $source, mixed $data = null);
}
