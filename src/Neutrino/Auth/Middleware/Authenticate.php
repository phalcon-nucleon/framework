<?php

declare(strict_types=1);

namespace Neutrino\Auth\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Foundation\Middleware\Controller as ControllerMiddleware;
use Neutrino\Http\Standards\StatusCode;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Phalcon\Events\Event;

/**
 * Lets authenticated users through (`auth` service): `'middleware' => Authenticate::class`.
 *
 * A guest gets a 401, or is redirected: `'middleware' => [Authenticate::class => '/login']`.
 *
 * Alternative without middleware: the `auth` access of `Phalcon\Auth` (`auth.access`), checked by
 * `Phalcon\Auth\Mvc\AuthDispatcherListener` on the dispatcher events.
 */
class Authenticate extends ControllerMiddleware implements BeforeInterface
{
    /**
     * @param string|null $redirectTo URI a guest is redirected to, instead of a 401
     */
    public function __construct(string $controllerClass, private readonly ?string $redirectTo = null)
    {
        parent::__construct($controllerClass);
    }

    public function before(Event $event, object $source, mixed $data = null)
    {
        /** @var \Phalcon\Auth\Manager $auth */
        $auth = $this->getDI()->getShared(Services::AUTH);

        if ($auth->check()) {
            return true;
        }

        /** @var \Phalcon\Http\Response $response */
        $response = $this->getDI()->getShared(Services::RESPONSE);

        if ($this->redirectTo !== null) {
            $response->redirect($this->redirectTo);
        } else {
            $response->setStatusCode(StatusCode::UNAUTHORIZED, (string) StatusCode::message(StatusCode::UNAUTHORIZED));
        }

        return false;
    }
}
