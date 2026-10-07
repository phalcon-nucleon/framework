<?php

declare(strict_types=1);

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Foundation\Http\Kernel as HttpKernel;
use Neutrino\Interfaces\Providable;
use Neutrino\View\Engines\EngineRegister;
use Phalcon\Assets\Manager as AssetsManager;
use Phalcon\Config\Config;
use Phalcon\Di\Injectable;
use Phalcon\Di\Service;
use Phalcon\Html\Escaper\EscaperInterface;
use Phalcon\Html\TagFactory;
use Phalcon\Mvc\View as MvcView;
use Phalcon\Tag;

/**
 * The `view` service, configured by `config/view.php` (`views_dir`, `partials_dir`, `layouts_dir`, `engines`),
 * and the services used by the templates: `tag` (and its alias `tagFactory`, a `Phalcon\Html\TagFactory`, as in
 * the `FactoryDefault` of Phalcon 5) and `assets`.
 *
 * `engines` maps an extension to an {@see EngineRegister} class, or to any definition accepted by
 * `View::registerEngines()`. The engines are built on the first render that needs them.
 */
class View extends Injectable implements Providable
{
    public function registering(): void
    {
        $di = $this->getDI();

        // Phalcon\Tag (static, deprecated by Phalcon), still resolvable by its class.
        $di->setService(Tag::class, new Service(Tag::class, true));

        $tagFactory = new Service(function () use ($di): TagFactory {
            /** @var EscaperInterface $escaper */
            $escaper = $di->getShared(Services::ESCAPER);

            return new TagFactory($escaper);
        }, true);
        // Volt 5 compiles the tag functions (`link_to()`, `form()`…) on the `tag` service, as a TagFactory.
        $di->setService(Services::TAG, $tagFactory);
        $di->setService(Services::TAG_FACTORY, $tagFactory);
        $di->setService(TagFactory::class, $tagFactory);

        $assets = new Service(function () use ($di): AssetsManager {
            /** @var TagFactory $tagFactory */
            $tagFactory = $di->getShared(Services::TAG_FACTORY);

            return new AssetsManager($tagFactory);
        }, true);
        $di->setService(Services::ASSETS, $assets);
        $di->setService(AssetsManager::class, $assets);

        $view = new Service(function () use ($di): MvcView {
            /** @var Config $config */
            $config = $di->getShared(Services::CONFIG);
            $settings = $config->path('view');
            $settings = $settings instanceof Config ? $settings : new Config();

            $view = new MvcView();
            $view->setDI($di);

            $viewsDir = $settings->get('views_dir');
            if (is_string($viewsDir) || $viewsDir instanceof Config) {
                $view->setViewsDir($viewsDir instanceof Config ? $viewsDir->toArray() : $viewsDir);
            }
            if (is_string($partialsDir = $settings->get('partials_dir'))) {
                $view->setPartialsDir($partialsDir);
            }
            if (is_string($layoutsDir = $settings->get('layouts_dir'))) {
                $view->setLayoutsDir($layoutsDir);
            }

            $engines = [];
            $configured = $settings->get('engines');
            foreach ($configured instanceof Config ? $configured->toArray() : [] as $extension => $engine) {
                $engines[(string) $extension] = is_string($engine) && is_subclass_of($engine, EngineRegister::class)
                    ? $engine::getRegisterClosure()
                    : $engine;
            }
            $view->registerEngines($engines);

            // HTTP kernel without implicit views: the actions render with $this->view->render(), which Phalcon 5
            // writes straight to the output unless the view is started (before the headers, the status and the
            // cookies of the response). Started by the kernel, which puts its content in the response.
            $app = $di->has(Services::APP) ? $di->getShared(Services::APP) : null;
            if ($app instanceof HttpKernel) {
                $app->startView($view);
            }

            return $view;
        }, true);
        $di->setService(Services::VIEW, $view);
        $di->setService(MvcView::class, $view);
    }
}
