<?php

declare(strict_types=1);

namespace Neutrino\Http\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Foundation\Middleware\Controller;
use Neutrino\Http\Standards\StatusCode;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Phalcon\Events\Event;
use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;

/**
 * Rejects the requests that are not AJAX requests (`X-Requested-With: XMLHttpRequest`, any case) with a 400.
 *
 * `Request::isAjax()` is not used: Phalcon 5 compares the header case-sensitively.
 */
class Ajax extends Controller implements BeforeInterface
{
    public function before(Event $event, object $source, mixed $data = null): bool
    {
        $di = $this->getDI();
        /** @var RequestInterface $request */
        $request = $di->getShared(Services::REQUEST);

        if (strtolower($request->getHeader('X-Requested-With')) === 'xmlhttprequest') {
            return true;
        }

        /** @var ResponseInterface $response */
        $response = $di->getShared(Services::RESPONSE);
        $response->setStatusCode(StatusCode::BAD_REQUEST, StatusCode::message(StatusCode::BAD_REQUEST));

        return false;
    }
}
