<?php

declare(strict_types=1);

namespace Test\Error\Writer;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Debug\Debugger;
use Neutrino\Error\Error;
use Neutrino\Error\Writer\View;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\View as MvcView;
use Test\TestCase\TestCase;

final class ViewTest extends TestCase
{
    private StubResponse $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->response = $this->mockService(Services::RESPONSE, new StubResponse());
    }

    protected function tearDown(): void
    {
        self::getConfig()->remove('error');
        Debugger::reset();

        parent::tearDown();
    }

    public function testNonFatalErrorIgnored(): void
    {
        $view = $this->mockService(Services::VIEW, MvcView::class);
        $view->expects($this->never())->method('start');

        $this->expectOutputString('');

        foreach ([E_WARNING, E_NOTICE, E_USER_ERROR, E_DEPRECATED] as $type) {
            (new View())->handle(Error::fromError($type, 'msg'));
        }

        $this->assertFalse($this->response->wasSent);
    }

    public function testWithoutView(): void
    {
        $this->getDI()->remove(Services::VIEW);

        (new View())->handle(Error::fromError(E_ERROR, 'msg'));

        $this->assertSent(View::DEFAULT_MESSAGE);
    }

    public function testResponseAlreadySent(): void
    {
        $this->getDI()->remove(Services::VIEW);
        $this->response->wasSent = true;

        $this->expectOutputString(View::DEFAULT_MESSAGE);

        (new View())->handle(Error::fromError(E_ERROR, 'msg'));
    }

    public function testDefaultMessage(): void
    {
        $view = $this->view();
        $view->expects($this->never())->method('render');
        $view->expects($this->once())->method('setContent')->with(View::DEFAULT_MESSAGE);
        $view->method('getContent')->willReturn(View::DEFAULT_MESSAGE);

        (new View())->handle(Error::fromException(new \RuntimeException('boom')));

        $this->assertSent(View::DEFAULT_MESSAGE);
    }

    public function testErrorView(): void
    {
        $this->config(['view' => ['path' => 'errors', 'file' => 'http500']]);
        $error = Error::fromError(E_PARSE, 'msg');
        $view = $this->view();
        $view->expects($this->once())->method('render')->with('errors', 'http500', ['error' => $error]);
        $view->expects($this->never())->method('setContent');
        $view->method('getContent')->willReturn('error view');

        (new View())->handle($error);

        $this->assertSent('error view');
    }

    public function testErrorController(): void
    {
        $this->config(['dispatcher' => ['namespace' => 'App\Http\Controllers', 'controller' => 'errors', 'action' => 'index']]);
        $error = Error::fromError(E_ERROR, 'msg');
        $view = $this->view();
        $view->expects($this->never())->method('render');
        $view->method('getContent')->willReturn('error controller');
        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class);
        $dispatcher->expects($this->once())->method('setNamespaceName')->with('App\Http\Controllers');
        $dispatcher->expects($this->once())->method('setControllerName')->with('errors');
        $dispatcher->expects($this->once())->method('setActionName')->with('index');
        $dispatcher->expects($this->once())->method('setParams')->with(['error' => $error]);
        $dispatcher->expects($this->once())->method('dispatch');

        (new View())->handle($error);

        $this->assertSent('error controller');
    }

    public function testDebugErrorPage(): void
    {
        Debugger::register($this->app);
        $view = $this->mockService(Services::VIEW, MvcView::class);
        $view->expects($this->never())->method('start');

        (new View())->handle(Error::fromException(new \RuntimeException('debug page')));

        $this->assertSame(500, $this->response->getStatusCode());
        $this->assertStringContainsString('<h1>RuntimeException</h1>', (string) $this->response->getContent());
        $this->assertStringContainsString('debug page', (string) $this->response->getContent());
    }

    /**
     * @param array<string, mixed> $error
     */
    private function config(array $error): void
    {
        $this->getDI()->getShared(Services::CONFIG)->merge(new Config(['error' => $error]));
    }

    /**
     * @return MvcView&\PHPUnit\Framework\MockObject\MockObject
     */
    private function view(): MvcView
    {
        $view = $this->mockService(Services::VIEW, MvcView::class);
        $view->expects($this->once())->method('start');
        $view->expects($this->once())->method('finish');

        return $view;
    }

    private function assertSent(string $content): void
    {
        $this->assertTrue($this->response->wasSent);
        $this->assertSame(500, $this->response->getStatusCode());
        $this->assertSame($content, $this->response->getContent());
    }
}
