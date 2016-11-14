<?php

namespace Luxury\Http\Middleware;

use Luxury\Exceptions\TokenMismatchException;
use Luxury\Foundation\Middleware\Controller;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Phalcon\Events\Event;

/**
 * Class Csrf
 *
 * Cross Site Request Forgery Middleware
 *
 * @package Luxury\Http\Middleware
 */
class Csrf extends Controller implements BeforeInterface
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
        $security     = $this->security;
        $request      = $this->request;
        $tokenChecked = false;

        // Prevent unsetted token session
        if (!$security->getSessionToken()) {
            $security->getToken();
            $security->getTokenKey();
        }

        if ($request->isAjax()) {
            $tokenChecked = $security->checkToken(
                $security->getTokenKey(),
                $request->getHeader('X_CSRF_' . strtoupper($security->getTokenKey()))
            );
        } elseif ($request->isPost() || $request->isPut()) {
            $tokenChecked = $security->checkToken();
        } elseif ($request->isGet() || $request->isDelete()) {
            $tokenChecked = $security->checkToken(
                $security->getTokenKey(),
                $request->getQuery($security->getTokenKey())
            );
        }

        if (!$tokenChecked) {
            throw new TokenMismatchException;
        }

        return true;
    }
}
