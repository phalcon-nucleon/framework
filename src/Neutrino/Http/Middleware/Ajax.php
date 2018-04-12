<?php

namespace Neutrino\Http\Middleware;

use Neutrino\Foundation\Middleware\Controller;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Phalcon\Events\Event;

/**
 * Class Ajax
 *
 * @package Neutrino\Http\Middleware
 */
class Ajax extends Controller implements BeforeInterface
{

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
        if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && "xmlhttprequest" === strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])) {
            return true;
        }

        $this->response->setStatusCode(400, 'Bad Request');

        return false;
    }
}
