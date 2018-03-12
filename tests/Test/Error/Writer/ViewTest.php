<?php

namespace Test\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Writer\View;
use Phalcon\Mvc\Dispatcher;
use Test\TestCase\TestCase;

class ViewTest extends TestCase
{

    public function dataHandle()
    {
        $error = Error::fromException(new \Exception());
        $data[] = [$error];

        $error = Error::fromError(E_ERROR, 'E_ERROR', __FILE__, __LINE__);
        $data[] = [$error];

        $error = Error::fromError(E_PARSE, 'E_PARSE', __FILE__, __LINE__);
        $data[] = [$error];

        $withs = [
            [null, []],
            ['view.noConfig', []],
            ['view.config', ['error' => [
                'view' => [
                    'path' => 'error.view.path',
                    'file' => 'error.view.file'
                ]
            ]]],
            ['dispatcher', ['error' => [
                'dispatcher' => [
                    'namespace'  => 'error.dispatcher.namespace',
                    'controller' => 'error.dispatcher.controller',
                    'action'     => 'error.dispatcher.action',
                ]
            ]]],
        ];

        $handles = [];
        foreach ($withs as $with) {
            foreach ($data as $key => $datum) {
                if ($with[0] != null) {
                    foreach (['true', 'false'] as $response) {
                        $handles[$key . '.' . $with[0] . '.' . $response] = array_merge($datum, $with, [$response]);
                    }
                } else {
                    $handles[$key . '.no-with'] = array_merge($datum, $with, ['false']);
                }
            }
        }

        $error = Error::fromError(E_USER_ERROR, 'E_USER_ERROR', __FILE__, __LINE__);
        $handles[] = [$error, 'nothing', [], 'nothing'];
        $error = Error::fromError(E_WARNING, 'E_WARNING', __FILE__, __LINE__);
        $handles[] = [$error, 'nothing', [], 'nothing'];
        $error = Error::fromError(E_NOTICE, 'E_USER_ERROR', __FILE__, __LINE__);
        $handles[] = [$error, 'nothing', [], 'nothing'];
        $error = Error::fromError(E_STRICT, 'E_STRICT', __FILE__, __LINE__);
        $handles[] = [$error, 'nothing', [], 'nothing'];

        return $handles;
    }

    /**
     * @dataProvider dataHandle
     *
     */
    public function testHandle($error, $with, $config, $response)
    {
        $config = $this->mockService(Services::CONFIG, new \Phalcon\Config($config), true);
        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);
        $view = $this->mockService(Services::VIEW, \Phalcon\Mvc\View::class, true);

        switch ($with) {
            case 'nothing':

            case null:
                if ($this->getDI()->has(Services::DISPATCHER)) {
                    $this->getDI()->remove(Services::DISPATCHER);
                }
                if ($this->getDI()->has(Services::VIEW)) {
                    $this->getDI()->remove(Services::VIEW);
                }
                $dispatcher->expects($this->never())->method('setNamespaceName');
                $dispatcher->expects($this->never())->method('setControllerName');
                $dispatcher->expects($this->never())->method('setActionName');
                $dispatcher->expects($this->never())->method('setParams');
                $dispatcher->expects($this->never())->method('dispatch');
                $view->expects($this->never())->method('start');
                $view->expects($this->never())->method('finish');
                $view->expects($this->never())->method('setContent');
                $view->expects($this->never())->method('getContent');
                $view->expects($this->never())->method('render');

                $this->expectOutputString('Whoops. Something went wrong.');
                break;
            case 'view.noConfig':
                $dispatcher->expects($this->never())->method('setNamespaceName');
                $dispatcher->expects($this->never())->method('setControllerName');
                $dispatcher->expects($this->never())->method('setActionName');
                $dispatcher->expects($this->never())->method('setParams');
                $dispatcher->expects($this->never())->method('dispatch');

                $view->expects($this->never())->method('render');
                $view->expects($this->once())->method('start');
                $view->expects($this->once())->method('finish');
                $view->expects($this->once())->method('setContent')->with('Whoops. Something went wrong.');
                $view->expects($this->once())->method('getContent')->willReturn('Whoops. Something went wrong.');

                break;
            case 'view.config':
                $dispatcher->expects($this->never())->method('setNamespaceName');
                $dispatcher->expects($this->never())->method('setControllerName');
                $dispatcher->expects($this->never())->method('setActionName');
                $dispatcher->expects($this->never())->method('setParams');
                $dispatcher->expects($this->never())->method('dispatch');

                $view->expects($this->once())->method('render')->with(
                    $config['error']['view']['path'],
                    $config['error']['view']['file'],
                    ['error' => $error]
                );
                $view->expects($this->once())->method('start');
                $view->expects($this->once())->method('finish');
                $view->expects($this->never())->method('setContent');
                $view->expects($this->once())->method('getContent')->willReturn('Whoops. Something went wrong.');

                break;
            case 'dispatcher':
                $dispatcher->expects($this->once())->method('setNamespaceName')->with($config['error']['dispatcher']['namespace']);
                $dispatcher->expects($this->once())->method('setControllerName')->with($config['error']['dispatcher']['controller']);
                $dispatcher->expects($this->once())->method('setActionName')->with($config['error']['dispatcher']['action']);
                $dispatcher->expects($this->once())->method('setParams')->with(['error' => $error]);
                $dispatcher->expects($this->once())->method('dispatch');

                $view->expects($this->once())->method('start');
                $view->expects($this->once())->method('finish');
                $view->expects($this->never())->method('setContent');
                $view->expects($this->once())->method('getContent')->willReturn('Whoops. Something went wrong.');
                break;
        }

        $mock = $this->mockService(Services::RESPONSE, \Phalcon\Http\Response::class, true);

        switch ($response) {
            case 'true':
                $mock->expects($this->once())
                    ->method('isSent')
                    ->will($this->returnValue(false));

                $mock->expects($this->once())
                    ->method('setContent')
                    ->with('Whoops. Something went wrong.')
                    ->will($this->returnSelf());
                $mock->expects($this->once())
                    ->method('setStatusCode')
                    ->with(500)
                    ->will($this->returnSelf());
                $mock->expects($this->once())
                    ->method('send');

                $this->expectOutputString(null);
                break;
            case 'false':
                $mock->expects($this->any())
                    ->method('isSent')
                    ->will($this->returnValue(true));

                $mock->expects($this->never())
                    ->method('setContent');
                $mock->expects($this->never())
                    ->method('setStatusCode');
                $mock->expects($this->never())
                    ->method('send');

                $this->expectOutputString('Whoops. Something went wrong.');
                break;
            case 'nothing':
                $dispatcher->expects($this->never())->method('setNamespaceName');
                $dispatcher->expects($this->never())->method('setControllerName');
                $dispatcher->expects($this->never())->method('setActionName');
                $dispatcher->expects($this->never())->method('setParams');
                $dispatcher->expects($this->never())->method('dispatch');

                $view->expects($this->never())->method('start');
                $view->expects($this->never())->method('finish');
                $view->expects($this->never())->method('setContent');
                $view->expects($this->never())->method('getContent');
                $view->expects($this->never())->method('render');

                $mock->expects($this->never())->method('isSent');
                $mock->expects($this->never())->method('setContent');
                $mock->expects($this->never())->method('setStatusCode');
                $mock->expects($this->never())->method('send');

                $this->expectOutputString(null);
                break;
        }

        $writer = new View();

        $writer->handle($error);
    }
}