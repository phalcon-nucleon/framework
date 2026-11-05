<?php

declare(strict_types=1);

/*
 * An HTTP request on a kernel of its own, run with php-cgi (see DebuggerHttpTest).
 *
 * Environment: NUCLEON_DEBUG (APP_DEBUG, "1" or "0"), REQUEST_URI ("/" a page, "/fail" an exception).
 * The page runs a query, reads the cache and writes a log.
 */

namespace DebugFixture {

    use Neutrino\Config\Config;
    use Neutrino\Constants\Services;
    use Neutrino\Foundation\Bootstrap;
    use Neutrino\Foundation\Http\Kernel;
    use Neutrino\Providers;
    use Phalcon\Mvc\Controller;

    require __DIR__ . '/../../../../vendor/autoload.php';

    \define('BASE_PATH', __DIR__);
    \define('APP_ENV', 'development');
    \define('APP_DEBUG', getenv('NUCLEON_DEBUG') === '1');

    ini_set('display_errors', '0');
    ini_set('log_errors', '0');

    final class DebugKernel extends Kernel
    {
        protected array $providers = [
            Providers\Http\Router::class,
            Providers\Http\Dispatcher::class,
            Providers\Database::class,
            Providers\Cache::class,
            Providers\Logger::class,
        ];

        protected array $middlewares = [];

        protected array $listeners = [];

        public function registerRoutes(): void
        {
            $router = $this->getDI()->getShared(Services::ROUTER);
            $router->addGet('/', ['namespace' => __NAMESPACE__, 'controller' => 'page', 'action' => 'index']);
            $router->addGet('/fail', ['namespace' => __NAMESPACE__, 'controller' => 'page', 'action' => 'fail']);
        }
    }

    final class PageController extends Controller
    {
        public function indexAction()
        {
            $this->getDI()->getShared(Services::DB)->fetchOne('SELECT 1 AS nucleon_query');
            $this->getDI()->getShared(Services::CACHE)->get('nucleon_cache_key');
            $this->getDI()->getShared(Services::LOGGER)->info('nucleon log message');

            return $this->response->setContent('<!DOCTYPE html><html><head></head><body><p>the page</p></body></html>');
        }

        public function failAction()
        {
            throw new \RuntimeException('nucleon failure', 0, new \LogicException('nucleon cause'));
        }
    }

    $bootstrap = new Bootstrap(new Config([
        'database' => ['default' => 'main', 'connections' => ['main' => ['adapter' => 'sqlite', 'config' => ['dbname' => ':memory:']]]],
        'cache'    => ['default' => 'memory', 'stores' => ['memory' => ['adapter' => 'memory']]],
        'log'      => ['adapters' => ['main' => ['adapter' => 'noop']]],
    ]));

    $bootstrap->run($bootstrap->make(DebugKernel::class));

    // Whether the debug classes were loaded.
    echo "\n<!-- debugger loaded: " . (class_exists(\Neutrino\Debug\Debugger::class, false) ? 'yes' : 'no') . ' -->';
}
