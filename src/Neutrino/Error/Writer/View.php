<?php

declare(strict_types=1);

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Debug\Debugger;
use Neutrino\Error\Error;
use Phalcon\Config\ConfigInterface;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\DispatcherInterface;
use Phalcon\Mvc\ViewInterface;

/**
 * Answers a fatal error with a 500 page (HTTP kernel).
 *
 * In debug mode ({@see Debugger}), the debug error page. Otherwise, by order of preference:
 * - an error controller: `error.dispatcher.namespace`, `error.dispatcher.controller` and `error.dispatcher.action`;
 * - an error view: `error.view.path` and `error.view.file`;
 * - "Whoops. Something went wrong.".
 *
 * The controller (in its params) and the view receive the error as `error`.
 */
final class View implements Writable
{
    public const string DEFAULT_MESSAGE = 'Whoops. Something went wrong.';

    public function handle(Error $error): void
    {
        if (!$error->isFatal()) {
            return;
        }

        // Drops what was output before the error (the page being rendered).
        if (ob_get_length() !== false) {
            ob_clean();
        }

        $di = Di::getDefault();

        if (Debugger::isEnabled()) {
            $this->send($di, Debugger::renderErrorPage($error));

            return;
        }

        $view = $di !== null && $di->has(Services::VIEW) ? $di->getShared(Services::VIEW) : null;

        if ($di === null || !$view instanceof ViewInterface) {
            $this->send($di, self::DEFAULT_MESSAGE);

            return;
        }

        $config = $di->has(Services::CONFIG) ? $di->getShared(Services::CONFIG) : null;
        $config = $config instanceof ConfigInterface ? $config : null;
        $controller = self::strings($config, 'error.dispatcher', ['namespace', 'controller', 'action']);
        $template = self::strings($config, 'error.view', ['path', 'file']);

        $view->start();

        if ($controller !== null && ($dispatcher = $di->getShared(Services::DISPATCHER)) instanceof DispatcherInterface) {
            $dispatcher->setNamespaceName($controller['namespace']);
            $dispatcher->setControllerName($controller['controller']);
            $dispatcher->setActionName($controller['action']);
            $dispatcher->setParams(['error' => $error]);
            $dispatcher->dispatch();
        } elseif ($template !== null) {
            $view->render($template['path'], $template['file'], ['error' => $error]);
        } else {
            $view->setContent(self::DEFAULT_MESSAGE);
        }

        $view->finish();

        $this->send($di, (string) $view->getContent());
    }

    private function send(?DiInterface $di, string $content): void
    {
        $response = $di !== null && $di->has(Services::RESPONSE) ? $di->getShared(Services::RESPONSE) : null;

        if ($response instanceof ResponseInterface && !$response->isSent()) {
            $response->setStatusCode(500, 'Internal Server Error');
            $response->setContent($content);
            $response->send();

            return;
        }

        echo $content;
    }

    /**
     * The string values of a config section, `null` when one is missing.
     *
     * @template K of string
     *
     * @param list<K> $keys
     *
     * @return array<K, string>|null
     */
    private static function strings(?ConfigInterface $config, string $path, array $keys): ?array
    {
        $values = [];

        foreach ($keys as $key) {
            $value = $config?->path($path . '.' . $key);

            if (!is_string($value) || $value === '') {
                return null;
            }

            $values[$key] = $value;
        }

        return $values;
    }
}
