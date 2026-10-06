<?php

declare(strict_types=1);

namespace Test\View;

use Neutrino\Constants\Services;
use Neutrino\Support\IdeHelper\Generator;
use Phalcon\Assets\Manager as AssetsManager;
use Phalcon\Html\TagFactory;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php;
use Phalcon\Tag;

final class ViewProviderTest extends ViewTestCase
{
    public function testServices(): void
    {
        foreach ([Services::VIEW, Services::TAG, Services::TAG_FACTORY, Services::ASSETS] as $service) {
            $this->assertTrue($this->di->getService($service)->isShared(), $service);
            $this->assertFalse($this->di->getService($service)->isResolved(), "$service is built at registration.");
        }

        $this->assertInstanceOf(View::class, $this->di->getShared(Services::VIEW));
        $this->assertSame($this->di->getShared(Services::VIEW), $this->di->getShared(View::class));
        $this->assertInstanceOf(TagFactory::class, $this->di->getShared(Services::TAG));
        $this->assertSame($this->di->getShared(Services::TAG), $this->di->getShared(Services::TAG_FACTORY));
        $this->assertInstanceOf(Tag::class, $this->di->getShared(Tag::class));
        $this->assertSame($this->di->getShared(Services::TAG_FACTORY), $this->di->getShared(TagFactory::class));
        $this->assertInstanceOf(AssetsManager::class, $this->di->getShared(Services::ASSETS));
        $this->assertSame($this->di->getShared(Services::ASSETS), $this->di->getShared(AssetsManager::class));
    }

    public function testIdeHelpersKnowTheServices(): void
    {
        $services = (new Generator($this->di))->services();

        $this->assertSame(View::class, $services[Services::VIEW]);
        $this->assertSame(TagFactory::class, $services[Services::TAG_FACTORY]);
        $this->assertSame(AssetsManager::class, $services[Services::ASSETS]);
    }

    public function testTagFunctions(): void
    {
        $this->assertSame('<a href="/about">About</a>', $this->renderString("{{ link_to('about', 'About') }}"));
    }

    public function testDirectories(): void
    {
        $this->container(['partials_dir' => 'partials/', 'layouts_dir' => 'layouts/']);
        /** @var View $view */
        $view = $this->di->getShared(Services::VIEW);

        $this->assertSame($this->dir . '/views/', $view->getViewsDir());
        $this->assertSame('partials/', $view->getPartialsDir());
        $this->assertSame('layouts/', $view->getLayoutsDir());
    }

    public function testRenderWithLayoutAndPartials(): void
    {
        $this->template('index', '<html>{{ content() }}</html>');
        $this->template('layouts/page', '<main>{{ content() }}</main>');
        $this->template('partials/header', '<h1>{{ title }}</h1>');
        $this->template('partials/footer', '<footer>{{ year }}</footer>');
        $this->template('post/show', "{{ partial('partials/header') }}<p>{{ body|e }}</p>{{ partial('partials/footer', ['year': 2026]) }}");

        $this->container(['layouts_dir' => 'layouts/']);
        $this->di->getShared(Services::VIEW)->setTemplateAfter('page');

        $this->assertSame(
            '<html><main><h1>Title</h1><p>a &lt;b&gt;</p><footer>2026</footer></main></html>',
            $this->render('post', 'show', ['title' => 'Title', 'body' => 'a <b>']),
        );
        $this->assertCount(5, glob($this->dir . '/compiled/*') ?: []);
    }

    public function testEngineDefinedWithoutRegister(): void
    {
        file_put_contents($this->dir . '/views/index.phtml', '<?= $name ?>!');
        $this->container(['engines' => ['.phtml' => Php::class]]);
        $this->di->getShared(Services::VIEW)->setRenderLevel(View::LEVEL_MAIN_LAYOUT);

        $this->assertSame('Nucleon!', $this->render('none', 'none', ['name' => 'Nucleon']));
    }
}
