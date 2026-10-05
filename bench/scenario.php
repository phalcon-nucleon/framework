<?php

/*
 * Runs ONE benchmark scenario once, in the current process, and prints its
 * measures on the last line. Called by bench/run.php in a fresh process for
 * each iteration.
 *
 *   php bench/scenario.php <scenario> <path/to/vendor/autoload.php>
 *
 * Must stay compatible with PHP 7.3: the same file measures Nucleon 1.3.
 */

use Bench\Kernels\CliKernel;
use Bench\Kernels\HttpKernel;
use Bench\Kernels\MicroKernel;
use Neutrino\Config\Loader as ConfigLoader;
use Neutrino\Dotconst;
use Neutrino\Foundation\Bootstrap;

if ($argc < 3) {
    fwrite(STDERR, "Usage: php bench/scenario.php <scenario> <autoload>\n");
    exit(1);
}

list(, $scenario, $autoload) = $argv;

require $autoload;

spl_autoload_register(function ($class) {
    if (strpos($class, 'Bench\\') === 0) {
        $file = __DIR__ . '/app/app/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

$start = hrtime(true);

Dotconst::load(__DIR__ . '/app');
$bootstrap = new Bootstrap(ConfigLoader::load(BASE_PATH));

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
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/hello';
        ob_start();
        $bootstrap->run($bootstrap->make(HttpKernel::class));
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
        $output = $kernel->handle();
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

    default:
        fwrite(STDERR, "Unknown scenario: $scenario\n");
        exit(1);
}

$elapsed = hrtime(true) - $start;

echo PHP_EOL, '@@BENCH@@', json_encode([
    'time_ns' => $elapsed,
    'memory_peak' => memory_get_peak_usage(),
]), PHP_EOL;
