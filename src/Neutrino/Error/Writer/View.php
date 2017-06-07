<?php

namespace Neutrino\Error\Writer;

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

    public function handle(Error $error)
    {
        if (!$error->isFateful()) {
            return;
        }

        $di     = Di::getDefault();

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
                    $view->setContent(Helper::format($error, false, true));
                }
                $view->finish();

                if ($di->has(Services::RESPONSE)
                    && ($response = $di->getShared(Services::RESPONSE)) instanceof Response
                    && !$response->isSent()
                ) {
                    $response
                        ->setStatusCode(500)
                        ->setContent($view->getContent())
                        ->send();

                    return;
                } else {
                    echo $view->getContent();

                    return;
                }
            }
        }

        echo Helper::format($error, false, true);
    }
}
