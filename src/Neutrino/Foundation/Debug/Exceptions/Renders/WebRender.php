<?php

namespace Neutrino\Foundation\Debug\Exceptions\Renders;

use Neutrino\Foundation\Debug\Debugger;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Debug\Exceptions\RenderInterface;
use Phalcon\DiInterface;
use Phalcon\Http\RequestInterface;
use Phalcon\Http\Response;

/**
 * Class WebRender
 * @package Neutrino\Foundation\Debug\Exceptions\Renders
 */
class WebRender implements RenderInterface
{
    /**
     * @param \Throwable|\Exception $throwable
     * @param \Phalcon\DiInterface  $container
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function render($throwable, $container = null)
    {
        if (Debugger::isEnable()) {
            return $this->makeResponse(Debugger::renderThrowable($throwable));
        }

        if ($container && $container->has(Services::REQUEST)) {
            if ($this->exceptJson($container->get(Services::REQUEST))) {
                return $this->renderJson($throwable);
            }

            return $this->renderHtml($throwable, $container);
        }

        return $this->makeResponse('Whoops. Something went wrong.');
    }

    /**
     * @param \Exception|\Throwable $throwable
     * @param DiInterface|null     $container
     *
     * @return Response
     */
    private function renderHtml($throwable, DiInterface $container = null)
    {
        $content = 'Whoops. Something went wrong.';

        if (!is_null($container)) {
            $config = [];
            if ($container->has(Services::CONFIG)) {
                $config = $container->getShared(Services::CONFIG);
            }

            if ($container->has(Services::VIEW)) {
                /* @var \Phalcon\Mvc\View $view */
                $view = $container->getShared(Services::VIEW);
                $view->start();
                if (
                    isset($config['error']['dispatcher']['namespace']) &&
                    isset($config['error']['dispatcher']['controller']) &&
                    isset($config['error']['dispatcher']['action'])
                ) {
                    /* @var \Phalcon\Mvc\Dispatcher $dispatcher */
                    $dispatcher = $container->getShared(Services::DISPATCHER);
                    $dispatcher->forward([
                      'namespace' => $config['error']['dispatcher']['namespace'],
                      'controller' => $config['error']['dispatcher']['controller'],
                      'action' => $config['error']['dispatcher']['action'],
                      'params' => ['thrown' => $throwable],
                    ]);
                    $dispatcher->dispatch();
                } elseif (
                    isset($config['error']['view']['path']) &&
                    isset($config['error']['view']['file'])
                ) {
                    $view->render(
                        $config['error']['view']['path'],
                        $config['error']['view']['file'],
                        ['thrown' => $throwable]
                    );
                } else {
                    $view->setContent('Whoops. Something went wrong.');
                }
                $view->finish();

                $content = $view->getContent();
            }
        }

        return $this->makeResponse($content, false);
    }

    /**
     * @param \Exception|\Throwable $throwable
     *
     * @return Response
     */
    private function renderJson($throwable)
    {
        $return = [
            'code' => 500,
            'status' => 'Internal Server Error',
        ];

        if (APP_DEBUG) {
            $return['debug'] = $throwable;
        }

        return $this->makeResponse($return, true);
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool
     */
    private function exceptJson(RequestInterface $request)
    {
        return in_array('application/json', (array)$request->getBestAccept());
    }

    /**
     * @param mixed $content
     * @param bool  $json
     *
     * @return Response
     */
    private function makeResponse($content, $json = false)
    {
        $response = new Response(null, 500, 'Internal Server Error');

        if ($json) {
            $response->setJsonContent($content);
        } else {
            $response->setContent($content);
        }

        return $response;
    }
}
