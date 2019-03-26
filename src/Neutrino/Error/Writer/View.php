<?php

namespace Neutrino\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Foundation\Debug\Debugger;
use Neutrino\Error\Error;
use Neutrino\Support\Arr;
use Phalcon\Di;
use Phalcon\Http\Response;

/**
 * Class View
 *
 * @deprecated
 *
 * @package     Neutrino\Error\Writer
 */
class View implements Writable
{

    /**
     * @inheritdoc
     * @deprecated
     */
    public function handle(Error $error)
    {
        if (!$error->isFateful()) {
            return;
        }

        foreach (ob_list_handlers() as $value) {
            ob_clean();
        }

        if (Debugger::isEnable()) {
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
                return;
            }
        }

        echo 'Whoops. Something went wrong.';
    }

    private function debugErrorView(Error $error)
    {
        if ($error->isException) {
            $throwable = $error->exception;
        } else {
            $throwable = \Neutrino\Debug\Exceptions\Helper::errorToThrowable(
                $error->code,
                $error->message,
                $error->file,
                $error->line
            );
        }

        $this->send(Debugger::renderThrowable($throwable));
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
