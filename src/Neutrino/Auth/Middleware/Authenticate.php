<?php

namespace Neutrino\Auth\Middleware;

use Neutrino\Auth\Exceptions\AuthenticationException;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Middleware\Controller as ControllerMiddleware;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Phalcon\Events\Event;

/**
 * Class Authenticate
 *
 * @package Neutrino\Auth\Middleware
 */
class Authenticate extends ControllerMiddleware implements BeforeInterface
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
        if (!$this->{Services::AUTH}->check()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return true;
    }
}
