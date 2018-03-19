<?php

namespace Neutrino\Providers;

use Neutrino\Constants\Services;
use Neutrino\Interfaces\Providable;
use Phalcon\Assets\Manager as AssetsManager;
use Phalcon\Di\Injectable;
use Phalcon\DiInterface;
use Phalcon\Tag;

/**
 * Class View
 *
 * @package Neutrino\Foundation\Bootstrap
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

            $engines = $configView->engines;
            $registerEngines = [];
            foreach ($engines as $type => $engine) {
                if(method_exists($engine, 'getRegisterClosure')){
                    $registerEngines[$type] = $engine::getRegisterClosure();
                } else {
                    $registerEngines[$type] = $engine;
                }
            }

            $view->registerEngines($registerEngines);

            return $view;
        });
    }
}
