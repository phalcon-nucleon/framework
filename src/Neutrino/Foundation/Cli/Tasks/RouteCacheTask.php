<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Http\RouteCompiler;
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
            RouteCompiler::write($this->loadHttpRouter(), BASE_PATH);

            $this->info("Success");
        } catch (\Exception $e) {
            $this->error("Error");
            $this->block([$e->getMessage()], 'error');

            RouteCompiler::clear(BASE_PATH);
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
}
