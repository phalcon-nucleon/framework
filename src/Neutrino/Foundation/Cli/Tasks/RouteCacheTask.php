<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Debug\Reflexion;
use Neutrino\PhpPreloader\Factory;
use Phalcon\Mvc\Router;

/**
 * Class RouteCacheTask
 *
 * @package Neutrino\Foundation\Cli\Tasks
 */
class RouteCacheTask extends Task
{
    /**
     * Generate a cache for http kernel's routes.
     */
    public function mainAction()
    {
        $this->output->write(Decorate::notice(str_pad('Generating http-routes cache', 40, ' ')), false);

        try {
            $router = $this->loadHttpRouter();

            $str = $this->compile($router);

            file_put_contents(BASE_PATH . '/bootstrap/compile/http-routes.php', "<?php\n$str");

            $this->info("Success");
        } catch (\Exception $e) {
            $this->error("Error");
            $this->block([$e->getMessage()], 'error');

            @unlink(BASE_PATH . '/bootstrap/compile/http-routes.php');
        }
    }

    /**
     * @return \Phalcon\Mvc\Router
     */
    private function loadHttpRouter()
    {
        $di = $this->getDI();

        $cliRouter = $di->get(Services::ROUTER);

        $di->remove(Services::ROUTER);
        $di->set(Services::ROUTER, new Router(false));

        include BASE_PATH . '/routes/http.php';

        $router = $di->get(Services::ROUTER);

        $di->remove(Services::ROUTER);
        $di->set(Services::ROUTER, $cliRouter);

        return $router;
    }

    private function compile(Router $router)
    {

        $str = "<?php\n";
        $str .= "\$router = \Phalcon\Di::getDefault()->getShared('router');\n";

        $fluents = [];
        foreach ([
                     '_defaultModule'      => 'setDefaultModule',
                     '_defaultNamespace'   => 'setDefaultNamespace',
                     '_defaultController'  => 'setDefaultController',
                     '_defaultAction'      => 'setDefaultAction',
                     '_removeExtraSlashes' => 'removeExtraSlashes',
                     '_notFoundPaths'      => 'notFound',
                 ] as $property => $method) {
            $var = Reflexion::get($router, $property);
            if (!is_null($var)) {
                $fluents[] = "$method(" . var_export($var, true) . ")\n";
            }
        }

        $defaultParams = Reflexion::get($router, '_defaultParams');

        if (!is_null($defaultParams) && [] !== $defaultParams) {
            $fluents[] = "setDefaults(" . var_export(['params' => $defaultParams], true) . ")\n";
        }

        if (!empty($fluents)) {
            $str .= "\$router->" . implode('->', $fluents) . ";";
        }

        foreach ($router->getRoutes() as $route) {
            $str .= "\$router->add("
                . var_export($route->getCompiledPattern(), true) . ","
                . var_export($route->getPaths(), true) . ","
                . var_export($route->getHttpMethods(), true) . ")";

            if (!empty($route->getName())) {
                $str .= "->setName('" . $route->getName() . "')";
            }
            if (!empty($route->getHostname())) {
                $str .= "->setHostname('" . $route->getHostname() . "')";
            }

            $str .= "\n;";
        }

        $preloader = (new Factory)->create();

        $stmts = $preloader->getParser()->parse($str);
        $stmts = $preloader->traverse($stmts);
        $str = $preloader->prettyPrint($stmts);

        return $str;
    }
}
