<?php

declare(strict_types=1);

/*
 * Runs an error in a process of its own, with the error handler registered.
 *
 * php handler.php <bootstrap|bootstrap-off|register> <warning|silenced|unreported|exception|compile|memory>
 *
 * - bootstrap: registered by Foundation\Bootstrap (production, `error.register` true);
 * - bootstrap-off: Foundation\Bootstrap with `error.register` false;
 * - register: Handler::register(), twice.
 *
 * The Cli writer prints the errors on the standard output, the Phplog writer on the error output.
 */

use Fake\Kernels\Cli\StubKernelCliEmpty;
use Neutrino\Config\Config;
use Neutrino\Error\Handler;
use Neutrino\Error\Writer;
use Neutrino\Foundation\Bootstrap;

require __DIR__ . '/../../../../vendor/autoload.php';

const BASE_PATH = __DIR__ . '/../../../.fake/nucleon.app';
const APP_ENV = 'production';
const APP_DEBUG = false;

ini_set('display_errors', '0');
ini_set('log_errors', '0');
ini_set('error_log', '');

[, $mode, $action] = $argv;

if ($mode === 'register') {
    Handler::setWriters([Writer\Phplog::class, Writer\Cli::class]);
    Handler::register();
    Handler::register();
} else {
    (new Bootstrap(new Config(['error' => ['register' => $mode === 'bootstrap']])))->make(StubKernelCliEmpty::class);
    Handler::setWriters([Writer\Phplog::class, Writer\Cli::class]);
}

switch ($action) {
    case 'warning':
        trigger_error('a warning', E_USER_WARNING);
        break;
    case 'silenced':
        @trigger_error('a silenced warning', E_USER_WARNING);
        break;
    case 'unreported':
        error_reporting(E_ALL & ~E_USER_NOTICE);
        trigger_error('an unreported notice', E_USER_NOTICE);
        break;
    case 'exception':
        throw new RuntimeException('an exception');
    case 'compile':
        $file = tempnam(sys_get_temp_dir(), 'nucleon-compile');
        file_put_contents($file, '<?php function nucleon_twice() {} function nucleon_twice() {}');
        register_shutdown_function(unlink(...), $file);
        require $file;
        break;
    case 'memory':
        ini_set('memory_limit', '16M');
        $data = str_repeat('x', 64 * 1024 * 1024);
        break;
}

echo "\nend of script\n";
