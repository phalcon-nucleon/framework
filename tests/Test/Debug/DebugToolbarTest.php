<?php

namespace Test\Debug;

use Neutrino\Constants\Events;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Debug\Debugger;
use Neutrino\Foundation\Debug\DebugToolbar;
use Neutrino\Debug\Reflexion;
use Phalcon\Events\Manager;
use Phalcon\Http\Response;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\View\Simple;
use Test\TestCase\TestCase;

class DebugToolbarTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        Reflexion::set(Debugger::class, 'instance', null);
        Reflexion::set(Debugger::class, 'view', null);

        parent::tearDownAfterClass();
    }

    private function mockResponse($code, array $headers = [], $strict = true)
    {
        $response = $this->mockService(Services::RESPONSE, Response::class, true);

        $response->expects($strict ? $this->once() : $this->atLeastOnce())->method('getStatusCode')->willReturn($code);

        if (!empty($headers)) {
            $response
                ->expects($strict ? $this->exactly(count($headers)) : $this->atLeast(count($headers)))
                ->method('getHeaders')
                ->willReturn($mheaders = $this->createMock(Response\Headers::class));

            $withs = [];
            foreach ($mheaders as $name => $header) {
                $withs[] = [$name];
            }

            $mheaders
                ->expects($strict ? $this->exactly(count($headers)) : $this->atLeast(count($headers)))
                ->method('get')
                ->withConsecutive(...$withs)
                ->willReturnOnConsecutiveCalls(...array_values($headers));
        }
    }

    public function testToolbarIsAllowedAjaxRequest()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = "XMLHttpRequest";
        $this->assertFalse(Reflexion::invoke(DebugToolbar::class, 'toolbarIsAllowed'));

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);

        $this->mockResponse(302);
        $this->assertFalse(Reflexion::invoke(DebugToolbar::class, 'toolbarIsAllowed'));

        $this->mockResponse(200, ['Content-Type' => 'json']);
        $this->assertFalse(Reflexion::invoke(DebugToolbar::class, 'toolbarIsAllowed'));

        $this->mockResponse(200, ['Content-Type' => false, 'Content-Disposition' => 'attachment;']);
        $this->assertFalse(Reflexion::invoke(DebugToolbar::class, 'toolbarIsAllowed'));

        $this->mockResponse(200, ['Content-Type' => false, 'Content-Disposition' => false]);
        $this->assertTrue(Reflexion::invoke(DebugToolbar::class, 'toolbarIsAllowed'));
    }

    private function mockHttpInfo()
    {
        $dispatcher = $this->mockService('dispatcher', Dispatcher::class, true);
        $router = $this->mockService('router', Router::class, true);

        $dispatcher->expects($this->once())->method('getModuleName')->willReturn('Module');
        $dispatcher->expects($this->once())->method('getHandlerClass')->willReturn('getHandlerClass');
        $dispatcher->expects($this->once())->method('getControllerName')->willReturn('getControllerName');
        $dispatcher->expects($this->once())->method('getActionName')->willReturn('getActionName');

        $router->expects($this->once())->method('getMatchedRoute')->willReturn(null);
    }

    public function testGetHttpInfo()
    {
        $this->mockHttpInfo();
        $this->mockResponse(200);

        $expected = [
            'requestHttpMethod' => 'GET',
            'responseHttpCode'  => 200,
            'dispatch'          => [
                'module'          => 'Module',
                'controllerClass' => 'getHandlerClass',
                'controller'      => 'getControllerName',
                'method'          => 'getActionName',
            ],
            'route'             => [
                'pattern' => null,
                'name'    => null,
                'id'      => null,
            ],
        ];

        $this->assertEquals($expected, Reflexion::invoke(DebugToolbar::class, 'getHttpInfo'));
    }

    public function testRender()
    {
        $this->mockHttpInfo();
        $this->mockResponse(200, ['Content-Type' => false, 'Content-Disposition' => false], false);

        $em = new Manager();

        Reflexion::set(Debugger::class, 'instance', (object)['em' => $em]);

        $this->expectOutputRegex('!^<style!');

        DebugToolbar::register();

        $em->fire(Events\Kernel::TERMINATE, $this);
    }
}
