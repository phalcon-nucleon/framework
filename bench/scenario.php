<?php

/*
 * Runs ONE benchmark scenario once, in the current process, and prints its
 * measures on the last line. Called by bench/run.php in a fresh process for
 * each iteration.
 *
 *   php bench/scenario.php <scenario> <path/to/vendor/autoload.php> [<app dir>]
 *
 * Must stay compatible with PHP 7.3: the same file measures Nucleon 1.3.
 */

use Bench\Kernels\CacheKernel;
use Bench\Kernels\CliKernel;
use Bench\Kernels\HttpKernel;
use Bench\Kernels\MicroKernel;
use Neutrino\Config\Loader as ConfigLoader;
use Neutrino\Dotconst;
use Neutrino\Foundation\Bootstrap;

if ($argc < 3) {
    fwrite(STDERR, "Usage: php bench/scenario.php <scenario> <autoload> [<app dir>]\n");
    exit(1);
}

$scenario = $argv[1];
$autoload = $argv[2];
$app = isset($argv[3]) ? $argv[3] : __DIR__ . '/app';

require $autoload;

spl_autoload_register(function ($class) use ($app) {
    if (strpos($class, 'Bench\\') === 0) {
        $file = $app . '/app/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

$start = hrtime(true);

// Compiled constants are used when the application was optimized, as in production.
Dotconst::load($app, $app . '/bootstrap/compile');
$config = ConfigLoader::load(BASE_PATH);
if ($scenario === 'http-throttle-redis') {
    $config->merge(new Phalcon\Config\Config(['security' => ['throttle' => ['store' => 'redis']]]));
}
$bootstrap = new Bootstrap($config);

switch ($scenario) {
    case 'boot-http':
        $bootstrap->make(HttpKernel::class)->boot();
        break;

    case 'boot-cli':
        $bootstrap->make(CliKernel::class)->boot();
        break;

    case 'boot-micro':
        $bootstrap->make(MicroKernel::class)->boot();
        break;

    case 'http':
    case 'http-mw1':
    case 'http-mw3':
    case 'http-throttle':
    case 'http-throttle-redis':
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $scenario === 'http' ? '/hello' : '/hello-' . substr($scenario, 5);
        if ($scenario === 'http-throttle-redis') {
            $_SERVER['REQUEST_URI'] = '/hello-throttle';
        }
        ob_start();
        $bootstrap->run($bootstrap->make(strpos($scenario, 'throttle') !== false ? CacheKernel::class : HttpKernel::class));
        $output = ob_get_clean();
        if ($output !== 'Hello') {
            fwrite(STDERR, "Unexpected HTTP output: $output\n");
            exit(1);
        }
        break;

    case 'micro':
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/hello';
        $_GET['_url'] = '/hello';
        $kernel = $bootstrap->make(MicroKernel::class);
        $kernel->boot();
        ob_start();
        // 2.x: the kernel reads the URI (handleIncoming); 1.3: the router reads $_GET['_url'].
        $output = method_exists($kernel, 'handleIncoming') ? $kernel->handleIncoming() : $kernel->handle();
        ob_end_clean();
        if ($output !== 'Hello') {
            fwrite(STDERR, "Unexpected Micro output\n");
            exit(1);
        }
        break;

    case 'cli':
        $kernel = $bootstrap->make(CliKernel::class);
        $kernel->setArgument(['nucleon', 'hello']);
        ob_start();
        $bootstrap->run($kernel);
        ob_end_clean();
        break;

    case 'service':
        // Lazy resolution of a provider-registered shared service, after boot.
        $kernel = $bootstrap->make(HttpKernel::class);
        $kernel->boot();
        $start = hrtime(true);
        $kernel->getDI()->getShared('url');
        break;

    case 'cache':
    case 'cache-100':
        // Resolution of the cache service, then set + get on its default `memory` store (1.3: save + get).
        $kernel = $bootstrap->make(CacheKernel::class);
        $kernel->boot();
        $start = hrtime(true);
        $cache = $kernel->getDI()->getShared('cache');
        $set = method_exists($cache, 'set') ? 'set' : 'save';
        for ($i = 0, $n = $scenario === 'cache' ? 1 : 100; $i < $n; $i++) {
            $cache->$set('key' . $i, ['value' => $i]);
            if ($cache->get('key' . $i) !== ['value' => $i]) {
                fwrite(STDERR, "Unexpected cache value\n");
                exit(1);
            }
        }
        break;

    default:
        fwrite(STDERR, "Unknown scenario: $scenario\n");
        exit(1);
}

$elapsed = hrtime(true) - $start;

echo PHP_EOL, '@@BENCH@@', json_encode([
    'time_ns' => $elapsed,
    'memory_peak' => memory_get_peak_usage(),
]), PHP_EOL;
