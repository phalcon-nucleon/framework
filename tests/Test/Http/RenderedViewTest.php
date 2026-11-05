<?php

declare(strict_types=1);

namespace Test\Http;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Http\Controller;
use Neutrino\Providers;
use Neutrino\View\Engines\Volt\VoltEngineRegister;
use Test\TestCase\TestCase;

/**
 * Without implicit views (`view.implicit`, the default), an action renders with $this->view->render(): its view
 * goes to the response, after the status and the headers (Phalcon 5 writes it straight to the output otherwise).
 */
final class RenderedViewTest extends TestCase
{
    private string $dir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = self::$cache_dir . 'rendered-view';
        mkdir($this->dir . '/views/pages', 0777, true);
        mkdir($this->dir . '/compiled');
        file_put_contents($this->dir . '/views/pages/missing.volt', '<p>{{ what }} not found</p>');

        $di = $this->getDI();
        $di->getShared(Services::CONFIG)->merge(new Config(['view' => [
            'views_dir'     => $this->dir . '/views/',
            'compiled_path' => $this->dir . '/compiled/',
            'engines'       => ['.volt' => VoltEngineRegister::class],
        ]]));
        ProviderRegistrar::register($di, [Providers\View::class]);

        foreach (['rendered', 'content', 'json'] as $action) {
            $this->app->router->addGet('/' . $action, ['namespace' => __NAMESPACE__, 'controller' => 'StubRender', 'action' => $action]);
        }
    }

    protected function tearDown(): void
    {
        self::getConfig()->remove('view');

        parent::tearDown();
    }

    public function testRenderedViewIsTheContentOfTheResponse(): void
    {
        $output = $this->dispatch('/rendered');

        $this->assertSame('<p>Page not found</p>', $output);
        $this->assertResponseCode(404);
        $this->assertSame('<p>Page not found</p>', $this->getContent());
    }

    public function testSeveralRequests(): void
    {
        $this->assertSame('<p>Page not found</p>', $this->dispatch('/rendered'));
        $this->assertSame('<p>Page not found</p>', $this->dispatch('/rendered'));
    }

    public function testContentSetByTheActionIsKept(): void
    {
        $this->assertSame('set by the action', $this->dispatch('/content'));
    }

    public function testActionWithoutView(): void
    {
        $this->assertSame('{"ok":true}', $this->dispatch('/json'));
        $this->assertFalse($this->getDI()->getService(Services::VIEW)->isResolved());
    }
}

final class StubRenderController extends Controller
{
    protected function onConstruct() {}

    public function renderedAction()
    {
        $this->response->setStatusCode(404, 'Not Found');
        $this->view->render('pages', 'missing', ['what' => 'Page']);
    }

    public function contentAction()
    {
        $this->view->render('pages', 'missing', ['what' => 'Page']);
        $this->response->setContent('set by the action');
    }

    public function jsonAction()
    {
        return $this->response->setJsonContent(['ok' => true]);
    }
}
