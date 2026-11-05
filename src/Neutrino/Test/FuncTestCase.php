<?php

declare(strict_types=1);

namespace Neutrino\Test;

use Neutrino\Constants\Services;
use Phalcon\Cli\Console;
use Phalcon\Cli\Dispatcher as CliDispatcher;
use Phalcon\Http\Request;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Mvc\Micro;
use ReflectionProperty;
use RuntimeException;

/**
 * Functional tests: dispatches requests (or command lines) through the booted kernel,
 * and asserts on the dispatcher and the response.
 */
abstract class FuncTestCase extends TestCase
{
    /**
     * Replaces a service with a mock (built from a class name) or with the given instance.
     *
     * @template T of object
     *
     * @param class-string<T>|T $class
     *
     * @return ($class is class-string<T> ? T&\PHPUnit\Framework\MockObject\MockObject : T)
     */
    public function mockService(string $service, string|object $class, bool $shared = true): object
    {
        $di = $this->getDI();

        if ($di->has($service)) {
            $di->remove($service);
        }

        $instance = is_string($class) ? $this->createMock($class) : $class;

        $di->set($service, $instance, $shared);

        return $instance;
    }

    /**
     * Asserts that the last dispatched controller matches the given controller name.
     */
    public function assertController(string $expected): void
    {
        $dispatcher = $this->dispatcher();
        $actual = $dispatcher instanceof CliDispatcher ? $dispatcher->getTaskName() : $dispatcher->getControllerName();

        self::assertSame(
            $expected,
            $actual,
            sprintf('Failed asserting Controller name "%s", actual Controller name is "%s"', $expected, $actual),
        );
    }

    /**
     * Asserts that the last dispatched action matches the given action name.
     */
    public function assertAction(string $expected): void
    {
        $actual = $this->dispatcher()->getActionName();

        self::assertSame(
            $expected,
            $actual,
            sprintf('Failed asserting Action name "%s", actual Action name is "%s"', $expected, $actual),
        );
    }

    /**
     * Asserts that the response has the given headers: `['Content-Type' => 'application/json']`.
     *
     * @param array<string, string> $expected
     */
    public function assertHeader(array $expected): void
    {
        $headers = $this->response()->getHeaders();

        foreach ($expected as $field => $value) {
            $actual = $headers->get($field);

            self::assertSame(
                $value,
                $actual,
                sprintf('Failed asserting "%s" has a value of "%s", actual "%s" header value is "%s"', $field, $value, $field, var_export($actual, true)),
            );
        }
    }

    /**
     * Asserts that the response status code matches the given one.
     */
    public function assertResponseCode(int $expected): void
    {
        $actual = $this->response()->getStatusCode();

        self::assertSame(
            $expected,
            $actual,
            sprintf('Failed asserting response code is "%d", actual response code is "%s"', $expected, var_export($actual, true)),
        );
    }

    /**
     * Asserts that the dispatch was forwarded.
     */
    public function assertDispatchIsForwarded(): void
    {
        self::assertTrue($this->dispatcher()->wasForwarded(), 'Failed asserting dispatch was forwarded');
    }

    /**
     * Asserts that the response redirects to the given location.
     */
    public function assertRedirectTo(string $location): void
    {
        $actual = $this->response()->getHeaders()->get('Location');

        self::assertNotFalse($actual, 'Failed asserting response caused a redirect');
        self::assertSame(
            $location,
            $actual,
            sprintf('Failed asserting response redirects to "%s". It redirects to "%s".', $location, (string) $actual),
        );
    }

    /**
     * Content of the response.
     */
    public function getContent(): string
    {
        return $this->response()->getContent();
    }

    /**
     * Asserts that the response content contains the given string.
     */
    public function assertResponseContentContains(string $string): void
    {
        self::assertStringContainsString($string, $this->getContent());
    }

    /**
     * Dispatches a request through the HTTP (or Micro) kernel and returns what the client receives: the output, then
     * the content of the response when it was not sent.
     *
     * Parameters go to `$_GET` for GET, HEAD and DELETE, to `$_POST` for POST, PUT and PATCH.
     * Headers go to `$_SERVER['HTTP_*']` (and `CONTENT_TYPE`, `CONTENT_LENGTH`).
     * `$json` is the request body, read by `Request::getJsonRawBody()`.
     * The superglobals are restored afterwards.
     *
     * @param array<string, mixed>  $params
     * @param array<string, string> $headers
     * @param array<mixed>|null     $json
     */
    protected function dispatch(string $url, string $method = 'GET', array $params = [], array $headers = [], ?array $json = null): string
    {
        $app = $this->kernelInstance();

        if (!$app instanceof Application && !$app instanceof Micro) {
            throw new RuntimeException(static::class . '::dispatch() needs an HTTP or a Micro kernel.');
        }

        $globals = [$_SERVER, $_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES];

        [$path, $query] = array_pad(explode('?', $url, 2), 2, '');
        parse_str($query, $queryParams);

        $method = strtoupper($method);
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $url;
        $_SERVER['QUERY_STRING'] = $query;
        $_GET = $queryParams;
        $_POST = [];

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $_POST = $params;
        } else {
            $_GET = $params + $_GET;
        }
        $_REQUEST = $_GET + $_POST;

        if ($json !== null) {
            $headers += ['Content-Type' => 'application/json'];
        }

        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            $_SERVER[in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true) ? $key : 'HTTP_' . $key] = $value;
        }

        $this->setRawBody($json === null ? '' : json_encode($json, JSON_THROW_ON_ERROR));

        ob_start();
        try {
            // handleIncoming() as Bootstrap::run(): the HTTP kernel puts the view of the action in the response.
            $response = $app->handleIncoming();

            $output = (string) ob_get_contents();

            if ($response instanceof ResponseInterface) {
                $this->setResponse($response);

                // What the client receives: Bootstrap::run() sends the response after the output.
                if (!$response->isSent()) {
                    $output .= (string) $response->getContent();
                }
            }

            return $output;
        } finally {
            ob_end_clean();
            [$_SERVER, $_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES] = $globals;
        }
    }

    /**
     * Dispatches a command line through the CLI kernel and returns what `handle()` returns.
     */
    protected function dispatchCli(string $cli): mixed
    {
        $app = $this->kernelInstance();

        if (!$app instanceof Console) {
            throw new RuntimeException(static::class . '::dispatchCli() needs a CLI kernel.');
        }

        $app->setArgument(explode(' ', $cli));

        return $app->handle();
    }

    private function dispatcher(): MvcDispatcher|CliDispatcher
    {
        /** @var MvcDispatcher|CliDispatcher */
        return $this->getDI()->getShared(Services::DISPATCHER);
    }

    private function response(): \Phalcon\Http\Response
    {
        /** @var \Phalcon\Http\Response */
        return $this->getDI()->getShared(Services::RESPONSE);
    }

    private function setResponse(ResponseInterface $response): void
    {
        $di = $this->getDI();

        if ($di->has(Services::RESPONSE) && $di->getShared(Services::RESPONSE) === $response) {
            return;
        }

        // The container caches resolved shared instances: remove the service before replacing it.
        $di->remove(Services::RESPONSE);
        $di->setShared(Services::RESPONSE, $response);
    }

    /**
     * The request body is read once from php://input: it is given to the request service directly.
     */
    private function setRawBody(string $body): void
    {
        $request = $this->getDI()->getShared(Services::REQUEST);

        if ($request instanceof Request) {
            (new ReflectionProperty(Request::class, 'rawBody'))->setValue($request, $body);
        }
    }
}
