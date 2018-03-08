<?php

namespace Neutrino\Debug;

use Neutrino\Constants\Events;
use Neutrino\Constants\Events\Kernel;
use Neutrino\Constants\Services;
use Neutrino\Dotconst;
use Neutrino\Error\Handler;
use Phalcon\Db\Adapter;
use Phalcon\Db\Profiler;
use Phalcon\Di;
use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\Manager;
use Phalcon\Mvc\View;

/**
 * Class DebugProvider
 *
 * Neutrino
 */
class Debugger extends Injectable
{

    /** @var Profiler */
    private static $dbProfiler;

    /** @var  array */
    private static $viewProfiles;

    /** @var Profiler[] */
    private static $profilers;

    /** @var self */
    private static $instance;

    private function __construct()
    {
        Handler::addWriter(DebugErrorLogger::class);

        $manager = $this->getGlobalEventManager();

        $this->listenLoader($manager);

        $this->listenServices($manager);

        $manager->attach(Kernel::TERMINATE, function () {
            $mem_peak = memory_get_peak_usage();
            $render_time = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

            $view = $this->getIsolateView();

            $view->setVar('mem_peak', $mem_peak);
            $view->setVar('render_time', $render_time);
            $view->setVar('events', DebugEventsManagerWrapper::getEvents());
            $view->setVars($this->getHttpInfo());
            $view->setVar('build', self::getBuildInfo());
            $view->setVar('php_errors', DebugErrorLogger::errors());
            $view->setVar('dbProfiles', self::getDbProfiles());
            $view->setVar('viewProfiles', self::getViewProfiles());
            $view->setVar('profilers', self::$profilers);

            echo $view->render('bar');
        });
    }

    /**
     * @return \Phalcon\Events\Manager
     */
    private function getGlobalEventManager()
    {
        /** @var Di $di */
        $di = $this->getDI();
        $app = $di->get(Services::APP);

        $em = $di->getInternalEventsManager();

        if (is_null($em)) {
            $di->setInternalEventsManager($em = new DebugEventsManagerWrapper(new Manager()));
        } else {
            $di->setInternalEventsManager($em = new DebugEventsManagerWrapper($em));
        }

        $em = $app->getEventsManager();

        if (is_null($em)) {
            $app->setEventsManager($em = $di->getInternalEventsManager());
        } else {
            $app->setEventsManager($em = new DebugEventsManagerWrapper($em));
        }

        return $em;
    }

    /**
     * @param \Phalcon\Events\Manager $manager
     */
    private function listenLoader($manager)
    {
        global $loader;

        /** @var \Phalcon\Loader $loader */
        if (isset($loader)) {
            $this->attachEventManager($loader, $manager);
        }
    }

    /**
     * @param \Phalcon\Events\Manager $manager
     */
    private function listenServices($manager)
    {
        /** @var Di $di */
        $di = $this->getDI();

        $em = $di->getInternalEventsManager();

        $em->attach('di:afterServiceResolve', function($ev, $src, $data) use ($manager) {
            static $resolved;

            if(isset($resolved[$data['name']])){
                return;
            }

            $resolved[$data['name']] = true;

            $this->tryAttachEventManager($data['instance'], $manager);

            if($data['instance'] instanceof Adapter\Pdo){
                $this->dbProfilerRegister($data['instance'], $manager);
            }
            if($data['instance'] instanceof View) {
                foreach ($data['instance']->getRegisteredEngines() as $engine) {
                    $this->tryAttachEventManager($engine, $manager);
                }
                $this->viewProfilerRegister($data['instance'], $manager);
            }
        });
    }

    private function tryAttachEventManager($service, $manager) {

        if ($service instanceof EventsAwareInterface
          || (method_exists($service, 'getEventsManager') && method_exists($service, 'setEventsManager'))) {
            $this->attachEventManager($service, $manager);
        }
    }

    /**
     * @param EventsAwareInterface $service
     * @param Manager $manager
     */
    private function attachEventManager($service, $manager)
    {
        $em = $service->getEventsManager();
        if ($em) {
            if (!($em instanceof DebugEventsManagerWrapper)) {
                $service->setEventsManager(new DebugEventsManagerWrapper($em));
            }
        } else {
            $service->setEventsManager($manager);
        }
    }

    /**
     * Register db profiler
     *
     * @param \Phalcon\Db\Adapter $db
     * @param \Phalcon\Events\Manager $manager
     */
    private function dbProfilerRegister($db, Manager $manager)
    {
        $profiler = new Profiler();

        $manager->attach(
          Events::DB,
          function (Event $event, Adapter\Pdo $connection) {
              $eventType = $event->getType();
              if ($eventType === 'beforeQuery') {
                  // Start a profile with the active connection
                  self::$dbProfiler->startProfile(
                    $connection->getSQLStatement(),
                    $connection->getSqlVariables(),
                    $connection->getSQLBindTypes()
                  );
              }
              if ($eventType === 'afterQuery') {
                  // Stop the active profile
                  self::$dbProfiler->stopProfile();
              }
          }
        );

        $db->setEventsManager($manager);

        self::$dbProfiler = $profiler;
    }

    /**
     * Register view profiler
     *
     * @param \Phalcon\Mvc\View $view
     * @param \Phalcon\Events\Manager $manager
     */
    private function viewProfilerRegister($view, Manager $manager)
    {
        $manager->attach(
          Events::VIEW,
          function (Event $event, $src, $data) {

              static $stores;

              $eventType = $event->getType();
              if ($eventType === 'beforeRender') {
                  self::$viewProfiles['render'] = [
                    'initialTime' => microtime(true)
                  ];
              } elseif ($eventType === 'beforeRenderView') {
                  $stores['renderViews'][] = self::$viewProfiles['renderViews'][] = [
                    'file' => $data,
                    'initialTime' => microtime(true)
                  ];
              } elseif ($eventType === 'afterRenderView') {
                  $profile = array_pop($stores['renderViews']);
                  $profile['finalTime'] = microtime(true);
                  $profile['elapsedTime'] = $profile['finalTime'] - $profile['initialTime'];

                  self::$viewProfiles['renderViews'][count($stores['renderViews'])] = $profile;
              } elseif ($eventType === 'notFoundView') {
                  self::$viewProfiles['notFoundView'][] = $data;
              } elseif ($eventType === 'afterRender') {
                  self::$viewProfiles['render']['finalTime'] = microtime(true);
                  self::$viewProfiles['render']['elapsedTime'] = self::$viewProfiles['render']['finalTime'] - self::$viewProfiles['render']['initialTime'];
              }
          }
        );

        $view->setEventsManager($manager);
    }

    private function getHttpInfo()
    {
        $module = $this->dispatcher->getModuleName();
        $controllerClass = $this->dispatcher->getHandlerClass();
        $controller = $this->dispatcher->getControllerName();
        $method = $this->dispatcher->getActionName();
        $route = $this->router->getMatchedRoute();
        $httpCode = $this->response->getStatusCode() ?: 200;
        $httpMethodRequest = $this->request->getMethod();

        return [
          'requestHttpMethod' => $httpMethodRequest,
          'responseHttpCode' => $httpCode,
          'dispatch' => [
            'module' => $module,
            'controllerClass' => $controllerClass,
            'controller' => $controller,
            'method' => $method,
          ],
          'route' => [
            'pattern' => $route ? $route->getPattern() : null,
            'name' => $route ? $route->getName() : null,
            'id' => $route ? $route->getRouteId() : null,
          ],
        ];
    }

    public static function register()
    {
        if(isset(self::$instance)){
            return;
        }

        self::$instance = new self;
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
        $build['neutrino']['const'] = Dotconst\Loader::fromFiles(BASE_PATH);

        return $build;
    }


    /**
     * @return \Phalcon\Db\Profiler\Item[]
     */
    public static function getDbProfiles()
    {
        return isset(self::$dbProfiler) ? self::$dbProfiler->getProfiles() : [];
    }

    /**
     * @return array
     */
    public static function getViewProfiles()
    {
        return self::$viewProfiles;
    }

    /**
     * @return \Phalcon\Mvc\View\Simple
     */
    public static function getIsolateView()
    {
        include __DIR__ . '/views/functions.php';

        $view = new View\Simple();
        $view->setDI(new Di());
        $view->setViewsDir(__DIR__ . '/views/');
        $view->registerEngines(
          [
            ".volt" => function ($view, $di) {
                $volt = new View\Engine\Volt($view, $di);
                $volt->setOptions([
                  "compiledPath" => Di::getDefault()->get('config')->view->compiled_path,
                  'compiledSeparator' => '_',
                  "compiledExtension" => ".compiled",
                  'compileAlways' => true,
                ]);
                $compiler = $volt->getCompiler();
                $compiler->addFunction('is_string', function ($resolvedArgs) {
                    return 'is_string(' . $resolvedArgs . ')';
                });
                $compiler->addFilter('human_mtime', function ($resolvedArgs) {
                    return __NAMESPACE__ . '\\human_mtime(' . $resolvedArgs . ')';
                });
                $compiler->addFilter('human_bytes', function ($resolvedArgs) {
                    return __NAMESPACE__ . '\\human_bytes(' . $resolvedArgs . ')';
                });
                $compiler->addFilter('sql_highlight', function ($resolvedArgs) {
                    return __NAMESPACE__ . '\\sql_highlight(' . $resolvedArgs . ')';
                });
                $compiler->addFilter('file_highlight', function ($resolvedArgs) {
                    return __NAMESPACE__ . '\\file_highlight(' . $resolvedArgs . ')';
                });
                $compiler->addFilter('func_highlight', function ($resolvedArgs) {
                    return __NAMESPACE__ . '\\func_highlight(' . $resolvedArgs . ')';
                });
                return $volt;
            },
          ]
        );

        return $view;
    }

    /**
     * @param string $name
     *
     * @return \Phalcon\Db\Profiler
     */
    public static function registerProfiler($name)
    {
        if(isset(self::$profilers[$name])){
            return self::$profilers[$name];
        }

        return self::$profilers[$name] = new Profiler();
    }
}
