<?php

declare(strict_types=1);

namespace Test\View;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers;
use Neutrino\View\Engines\Volt\Compiler\Extensions;
use Neutrino\View\Engines\Volt\Compiler\Filters;
use Neutrino\View\Engines\Volt\Compiler\Functions;
use Neutrino\View\Engines\Volt\VoltEngineRegister;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use PHPUnit\Framework\TestCase;

/**
 * A container with the view providers, templates in a temporary directory.
 */
abstract class ViewTestCase extends TestCase
{
    protected string $dir = '';

    protected FactoryDefault $di;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/nucleon-views-' . getmypid() . '-' . bin2hex(random_bytes(3));
        mkdir($this->dir . '/views/layouts', 0777, true);
        mkdir($this->dir . '/views/partials', 0777, true);
        mkdir($this->dir . '/compiled');

        $this->di = $this->container();
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
        Di::reset();
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewConfig(): array
    {
        return [
            'views_dir'     => $this->dir . '/views/',
            'compiled_path' => $this->dir . '/compiled/',
            'engines'       => ['.volt' => VoltEngineRegister::class],
            'extensions'    => [Extensions\PhpFunctionExtension::class, Extensions\StrExtension::class, Extensions\CsrfExtension::class],
            'filters'       => ['round' => Filters\RoundFilter::class, 'merge' => Filters\MergeFilter::class, 'split' => Filters\SplitFilter::class],
            'functions'     => ['route' => Functions\RouteFunction::class],
        ];
    }

    /**
     * @param array<string, mixed> $view Merged into viewConfig()
     */
    protected function container(array $view = []): FactoryDefault
    {
        $di = new FactoryDefault();
        Di::setDefault($di);
        $di->setShared(Services::CONFIG, new Config(['view' => array_replace($this->viewConfig(), $view), 'app' => ['base_uri' => '/']]));

        ProviderRegistrar::register($di, [Providers\Escaper::class, Providers\Url::class, Providers\View::class]);

        return $this->di = $di;
    }

    protected function template(string $name, string $content): void
    {
        $file = $this->dir . '/views/' . $name . '.volt';
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, $content);
    }

    /**
     * Renders `views/<controller>/<action>.volt` with its layouts.
     *
     * @param array<string, mixed> $params
     */
    protected function render(string $controller, string $action, array $params = []): string
    {
        /** @var View $view */
        $view = $this->di->getShared(Services::VIEW);
        $view->setVars($params);
        $view->start();
        $view->render($controller, $action);
        $view->finish();

        return (string) $view->getContent();
    }

    /**
     * Renders a template string (`views/inline/<n>.volt`), without layout.
     *
     * @param array<string, mixed> $params
     */
    protected function renderString(string $template, array $params = []): string
    {
        static $n = 0;
        $action = 't' . ++$n;
        $this->template('inline/' . $action, $template);

        /** @var View $view */
        $view = $this->di->getShared(Services::VIEW);
        $view->setRenderLevel(View::LEVEL_ACTION_VIEW);

        try {
            return $this->render('inline', $action, $params);
        } finally {
            $view->setRenderLevel(View::LEVEL_MAIN_LAYOUT);
        }
    }

    protected function compiler(): Compiler
    {
        /** @var View $view */
        $view = $this->di->getShared(Services::VIEW);

        return (new VoltEngineRegister())->register($view, $this->di)->getCompiler();
    }
}
