<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Env;
use Neutrino\Constants\Services;
use Neutrino\Interfaces\Providable;
use Neutrino\View\Engine\Extensions\PhpFunction as PhpFunctionExtension;
use Phalcon\Assets\Manager as AssetsManager;
use Phalcon\Di\Injectable;
use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Tag;

/**
 * Class View
 *
 *  @package Neutrino\Foundation\Bootstrap
 */
class View extends Injectable implements Providable
{
    /**
     * @inheritdoc
     */
    public function registering()
    {
        $di = $this->getDI();

        $di->setShared(Services::TAG, Tag::class);
        $di->setShared(Tag::class, Tag::class);
        $di->setShared(Services::ASSETS, AssetsManager::class);
        $di->setShared(AssetsManager::class, AssetsManager::class);

        $di->setShared(Services::VIEW, function () {
            /** @var DiInterface $this */

            $view = new \Phalcon\Mvc\View();

            $configView = $this->getShared(Services::CONFIG)->view;
            if (isset($configView->views_dir)) {
                $view->setViewsDir($configView->views_dir);
            }
            if (isset($configView->partials_dir)) {
                $view->setPartialsDir($configView->partials_dir);
            }
            if (isset($configView->layouts_dir)) {
                $view->setLayoutsDir($configView->layouts_dir);
            }

            $view->registerEngines([
                '.volt'  => function ($view, $di) {
                    /* @var \Phalcon\Di $di */
                    $volt = new VoltEngine($view, $di);

                    $config = $di->getShared(Services::CONFIG)->view;

                    $options = array_merge(
                        [
                            'compiledPath' => $config->compiled_path,
                            'compiledSeparator' => '_',
                        ],
                        [
                            'compileAlways' => APP_ENV === Env::DEVELOPMENT,
                        ],
                        isset($config->options) ? (array)$config->options : []
                    );

                    $volt->setOptions($options);

                    $volt->getCompiler()->addExtension(new PhpFunctionExtension());

                    return $volt;
                },
                '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
            ]);

            return $view;
        });
    }
}
