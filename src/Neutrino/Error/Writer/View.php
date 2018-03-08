<?php

namespace Neutrino\Error\Writer;

use Neutrino\Debug\DebugErrorLogger;
use Neutrino\Debug\DebugEventsManagerWrapper;
use Neutrino\Debug\Debugger;
use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Support\Arr;
use Phalcon\Di;
use Phalcon\Http\Response;

/**
 * Class View
 *
 * @package     Neutrino\Error\Writer
 */
class View implements Writable
{

    /**
     * @inheritdoc
     */
    public function handle(Error $error)
    {
        if (!$error->isFateful()) {
            return;
        }

        if (APP_DEBUG) {
            $this->debugErrorView($error);
            return;
        }

        $di = Di::getDefault();

        if (!is_null($di)) {
            $config = [];
            if($di->has(Services::CONFIG)){
                $config = $di->getShared(Services::CONFIG);
            }

            if ($di->has(Services::VIEW)) {
                /* @var \Phalcon\Mvc\View $view */
                $view = $di->getShared(Services::VIEW);
                $view->start();
                if (Arr::has($config, 'error.dispatcher.namespace')
                    && Arr::has($config, 'error.dispatcher.controller')
                    && Arr::has($config, 'error.dispatcher.action')
                ) {
                    /* @var \Phalcon\Mvc\Dispatcher $dispatcher */
                    $dispatcher = $di->getShared(Services::DISPATCHER);
                    $dispatcher->setNamespaceName(Arr::get($config, 'error.dispatcher.namespace'));
                    $dispatcher->setControllerName(Arr::get($config, 'error.dispatcher.controller'));
                    $dispatcher->setActionName(Arr::get($config, 'error.dispatcher.action'));
                    $dispatcher->setParams(['error' => $error]);
                    $dispatcher->dispatch();
                } elseif (Arr::has($config, 'error.view.path')
                    && Arr::has($config, 'error.view.file')
                ) {
                    $view->render(
                        Arr::get($config, 'error.view.path'),
                        Arr::get($config, 'error.view.file'),
                        ['error' => $error]
                    );
                } else {
                    $view->setContent('Whoops. Something went wrong.');
                }
                $view->finish();

                $this->send($view->getContent());
            }
        }

        echo 'Whoops. Something went wrong.';
    }

    private function debugErrorView(Error $error)
    {
        $view = Debugger::getIsolateView();

        $exceptions = [];

        if ($isException = $error->isException) {
            $exception = $error->exception;

            do {
                $exceptions[] = [
                  'class' => get_class($exception),
                  'code' => $exception->getCode(),
                  'message' => $exception->getMessage(),
                  'file' => $exception->getFile(),
                  'line' => $exception->getLine(),
                  'traces' => Helper::formatExceptionTrace($exception),
                ];
            } while ($exception = $exception->getPrevious());
        }

        $view->setVars([
          'error' => $error,
          'isException' => $isException,
          'exceptions' => $exceptions,
          'php_errors' => DebugErrorLogger::errors(),
          'dbProfiles' => Debugger::getDbProfiles(),
          'events' => DebugEventsManagerWrapper::getEvents(),
          'build' => Debugger::getBuildInfo(),
        ]);

        $this->send($view->render('errors'));
    }

    private function send($content)
    {
        $di = Di::getDefault();

        if ($di->has(Services::RESPONSE)
          && ($response = $di->getShared(Services::RESPONSE)) instanceof Response
          && !$response->isSent()
        ) {
            $response->setStatusCode(500, 'Internal Server Error');
            $response->setContent($content);
            $response->send();
            return;
        }

        echo $content;
    }
}
