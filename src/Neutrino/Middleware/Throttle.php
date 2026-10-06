<?php

declare(strict_types=1);

namespace Neutrino\Middleware;

use Neutrino\Constants\Services;
use Neutrino\Foundation\Middleware\Controller as ControllerMiddleware;
use Neutrino\Http\Standards\StatusCode;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Security\RateLimiter;
use Phalcon\Events\Event;
use RuntimeException;

/**
 * Limits the requests of a client to `$max` per window of `$decay` seconds ({@see RateLimiter}), and sends the
 * `X-RateLimit-Limit`, `X-RateLimit-Remaining` and `Retry-After` headers.
 *
 * A request is identified by its route (module, namespace, controller, action), host, URI and client address.
 */
abstract class Throttle extends ControllerMiddleware implements BeforeInterface, AfterInterface
{
    /**
     * Name of the rate limiter: separates the counters of each kind of throttle.
     */
    protected string $name;

    private ?RateLimiter $limiter = null;

    private ?string $signature = null;

    /**
     * Requests left, known after the hit of this request: the headers do not read the counter again.
     */
    private ?int $remaining = null;

    /**
     * @param int $max   Requests allowed per window
     * @param int $decay Length of the window, in seconds
     */
    public function __construct(string $controllerClass, private readonly int $max, private readonly int $decay = 60)
    {
        parent::__construct($controllerClass);

        if (!isset($this->name) || $this->name === '') {
            throw new RuntimeException(static::class . '->name is empty.');
        }
    }

    public function before(Event $event, object $source, mixed $data = null)
    {
        $signature = $this->resolveRequestSignature();
        $limiter = $this->getLimiter();

        // The count after the increment decides: concurrent requests cannot all pass a check made before it.
        $this->remaining = $limiter->attempt($signature, $this->max, $this->decay);

        if ($this->remaining === null) {
            $this->addHeaders($signature, true);

            return false;
        }

        $this->addHeaders($signature, false);

        return true;
    }

    public function after(Event $event, object $source, mixed $data = null)
    {
        $this->addHeaders($this->resolveRequestSignature(), false);

        return true;
    }

    /**
     * Signature of the request: module, namespace, controller, action | host | URI | client address.
     */
    protected function resolveRequestSignature(): string
    {
        if ($this->signature !== null) {
            return $this->signature;
        }

        /** @var \Phalcon\Http\Request $request */
        $request = $this->getDI()->getShared(Services::REQUEST);
        /** @var \Phalcon\Mvc\Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);

        return $this->signature = hash('xxh128', implode("\0", [
            $router->getModuleName(),
            $router->getNamespaceName(),
            $router->getControllerName(),
            $router->getActionName(),
            $request->getHttpHost(),
            $request->getURI(),
            (string) $request->getClientAddress(),
        ]));
    }

    protected function addHeaders(string $signature, bool $tooManyAttempts): void
    {
        /** @var \Phalcon\Http\Response $response */
        $response = $this->getDI()->getShared(Services::RESPONSE);
        $limiter = $this->getLimiter();

        $response->setHeader('X-RateLimit-Limit', (string) $this->max);

        if (!$tooManyAttempts) {
            $response->setHeader('X-RateLimit-Remaining', (string) ($this->remaining ?? $limiter->retriesLeft($signature, $this->max)));

            return;
        }

        $message = (string) StatusCode::message(StatusCode::TOO_MANY_REQUESTS);

        $response
            ->setContent($message)
            ->setStatusCode(StatusCode::TOO_MANY_REQUESTS, $message)
            ->setHeader('X-RateLimit-Remaining', '0')
            ->setHeader('Retry-After', (string) $limiter->availableIn($signature));
    }

    protected function getLimiter(): RateLimiter
    {
        if ($this->limiter === null) {
            $this->limiter = new RateLimiter($this->name);
            $this->limiter->setDI($this->getDI());
        }

        return $this->limiter;
    }
}
