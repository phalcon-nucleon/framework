<?php

declare(strict_types=1);

namespace Fake\Kernels\Http\Middlewares;

use Neutrino\Foundation\Middleware\Controller;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Phalcon\Events\Event;

/**
 * Route middleware recording its constructor parameters and its calls.
 */
class StubRouteMiddleware extends Controller implements BeforeInterface
{
    /** @var list<array{class-string, list<mixed>}> */
    public static array $calls = [];

    /** @var list<mixed> */
    private array $params;

    public function __construct(string $controllerClass, mixed ...$params)
    {
        parent::__construct($controllerClass);

        $this->params = array_values($params);
    }

    public function before(Event $event, object $source, mixed $data = null): bool
    {
        self::$calls[] = [static::class, $this->params];

        return true;
    }
}
