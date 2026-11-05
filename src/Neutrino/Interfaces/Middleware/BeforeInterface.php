<?php

declare(strict_types=1);

namespace Neutrino\Interfaces\Middleware;

use Phalcon\Events\Event;

interface BeforeInterface
{
    /**
     * Called before the handler. Returning false stops the request.
     *
     * No return type is imposed, so that implementations may return nothing:
     * only `false` has an effect (it stops cancelable events).
     *
     * @param object $source The application, the dispatcher…
     * @param mixed  $data   Data of the event
     *
     * @return bool|null|void
     */
    public function before(Event $event, object $source, mixed $data = null);
}
