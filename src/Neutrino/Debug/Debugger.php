<?php

namespace Neutrino\Debug;

use Neutrino\Constants\Events;
use Neutrino\Constants\Services;
use Neutrino\Dotconst;
use Neutrino\Error\Handler;
use Neutrino\Support\Str;
use Phalcon\Cli\Console;
use Phalcon\Db\Adapter;
use Phalcon\Db\Profiler;
use Phalcon\Di;
use Phalcon\Events\Event;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\Manager;
use Phalcon\Mvc\View;

/**
 * Class DebugProvider
 *
 * Neutrino
 */
class Debugger
{
    /** @var array */
    private static $viewProfiles;

    /** @var Profiler[] */
    private static $profilers;

    /** @var self */
    private static $instance;

    /** @var View\Simple */
    private static $view;

    /** @var \Neutrino\Debug\DebugEventsManagerWrapper */
    private $em;

    private function __construct()
    {
        self::$instance = $this;

        $di = Di::getDefault();

        if ($di->get(Services::APP) instanceof Console) {
            return;
        }

        Handler::addWriter(DebugErrorLogger::class);

        $this->registerGlobalEventManager();

        $this->listenLoader();

        $this->listenServices();

        DebugToolbar::register();
    }

    /**
     * @return \Phalcon\Events\Manager
     */
    private function registerGlobalEventManager()
    {
        /** @var Di $di */
        $di = Di::getDefault();

        if ($di->has(Services::EVENTS_MANAGER)) {
            $di->setShared(Services::EVENTS_MANAGER, $gem = new DebugEventsManagerWrapper($di->get(Services::EVENTS_MANAGER)));
        } else {
            $di->setShared(Services::EVENTS_MANAGER, $gem = new DebugEventsManagerWrapper(new Manager()));
        }

        $app = $di->get(Services::APP);
        $em = $app->getEventsManager();
        if (is_null($em)) {
            $app->setEventsManager($gem);
        } else {
            $app->setEventsManager($gem = new DebugEventsManagerWrapper($em));
        }

        $em = $di->getInternalEventsManager();
        if (is_null($em)) {
            $di->setInternalEventsManager($gem);
        } else {
            $di->setInternalEventsManager($gem = new DebugEventsManagerWrapper($em));
        }

        return $this->em = $gem;
    }

    private function listenLoader()
    {
        global $loader;

        /** @var \Phalcon\Loader $loader */
        if (isset($loader)) {
            $this->attachEventsManager($loader);
        }
    }

    private function listenServices()
    {
        $this->em->attach('di:afterServiceResolve', function ($ev, $src, $data) {
            static $resolved;

            if (isset($resolved[$data['name']])) {
                return;
            }

            $resolved[$data['name']] = true;

            $this->tryAttachEventsManager($data['instance']);

            if ($data['instance'] instanceof Adapter\Pdo) {
                $this->dbProfilerRegister();
            }
            if ($data['instance'] instanceof View) {
                try {
                    $engines = (array)Reflexion::get($data['instance'], '_engines');
                } catch (\Exception $e) {
                    $engines = [];
                }
                foreach ($engines as $engine) {
                    $this->tryAttachEventsManager($engine);
                }
                $this->viewProfilerRegister();
            }
        });
    }

    private function tryAttachEventsManager($service)
    {
        if ($service instanceof EventsAwareInterface
          || (method_exists($service, 'getEventsManager') && method_exists($service, 'setEventsManager'))) {
            $this->attachEventsManager($service);
        }
    }

    /**
     * @param EventsAwareInterface $service
     */
    private function attachEventsManager($service)
    {
        $em = $service->getEventsManager();
        if ($em) {
            if (!($em instanceof DebugEventsManagerWrapper)) {
                $service->setEventsManager(new DebugEventsManagerWrapper($em));
            }
        } else {
            $service->setEventsManager($this->em);
        }
    }

    /**
     * Register db profiler
     */
    private function dbProfilerRegister()
    {
        $profiler = self::registerProfiler('db', '<i class="nuc db"></i>');

        $this->em->attach(
          Events::DB,
          function (Event $event, Adapter\Pdo $connection) use ($profiler) {
              $eventType = $event->getType();
              if ($eventType === 'beforeQuery') {
                  // Start a profile with the active connection
                  $profiler->startProfile(
                    $connection->getSQLStatement(),
                    $connection->getSqlVariables(),
                    $connection->getSQLBindTypes()
                  );
              }
              if ($eventType === 'afterQuery') {
                  // Stop the active profile
                  $profiler->stopProfile();
              }
          }
        );
    }

    /**
     * Register view profiler
     */
    private function viewProfilerRegister()
    {
        $this->em->attach(
          Events::VIEW,
          function (Event $event, $src, $data) {
              $eventType = $event->getType();
              if ($eventType === 'beforeRender') {
                  self::$viewProfiles['render'][] = self::$viewProfiles['__render'][] = [
                    'initialTime' => microtime(true)
                  ];
              } elseif ($eventType === 'beforeRenderView') {
                  self::$viewProfiles['__renderViews'][] = self::$viewProfiles['renderViews'][] = [
                    'file' => $data,
                    'initialTime' => microtime(true)
                  ];
              } elseif ($eventType === 'afterRenderView') {
                  $profile = array_pop(self::$viewProfiles['__renderViews']);
                  $profile['finalTime'] = microtime(true);
                  $profile['elapsedTime'] = $profile['finalTime'] - $profile['initialTime'];

                  self::$viewProfiles['renderViews'][count(self::$viewProfiles['__renderViews'])] = $profile;
              } elseif ($eventType === 'notFoundView') {
                  self::$viewProfiles['notFoundView'][] = $data;
              } elseif ($eventType === 'afterRender') {
                  $profile = array_pop(self::$viewProfiles['__render']);
                  $profile['finalTime'] = microtime(true);
                  $profile['elapsedTime'] = $profile['finalTime'] - $profile['initialTime'];

                  self::$viewProfiles['render'][count(self::$viewProfiles['__render'])] = $profile;
              }
          }
        );
    }

    public static function register()
    {
        if (self::isEnable()) {
            return;
        }

        new self;
    }

    public static function isEnable()
    {
        return isset(self::$instance);
    }

    public static function getGlobalEventsManager()
    {
        if (!isset(self::$instance)) {
            throw new \Exception("Debugger wasn't registered");
        }

        return self::$instance->em;
    }

    public static function getBuildInfo()
    {
        $build = [
          'php' => [
            'version' => PHP_VERSION,
          ],
          'zend' => [
            'version' => zend_version(),
          ],
          'phalcon' => [
            'version' => \Phalcon\Version::get(),
          ],
          'neutrino' => [
            'version' => \Neutrino\Version::get(),
          ],
        ];

        foreach (get_loaded_extensions(true) as $extension) {
            $build['zend']['extensions'][$extension] = phpversion($extension);
        }
        foreach (get_loaded_extensions(false) as $extension) {
            $build['php']['extensions'][$extension] = phpversion($extension);
        }

        $build['phalcon']['ini'] = ini_get_all('phalcon');

        return $build;
    }

    /**
     * @return array
     */
    public static function getViewProfiles()
    {
        return self::$viewProfiles;
    }

    public static function getRegisteredProfilers()
    {
        return self::$profilers;
    }

    public static function internalRender($file, $params)
    {
        include __DIR__ . '/helpers/functions.php';

        extract($params);

        return include __DIR__ . '/resources/' . $file . '.html.php';
    }

    /**
     * @param string $name
     * @param string|null $icon
     *
     * @return \Phalcon\Db\Profiler
     */
    public static function registerProfiler($name, $icon = null)
    {
        if (isset(self::$profilers[$name])) {
            return self::$profilers[$name]['profiler'];
        }

        self::$profilers[$name] = [
          'icon' => $icon,
          'profiler' => $profiler = new Profiler()
        ];

        return $profiler;
    }
}
