<?php

/*
 * Runs the 1.3 `optimize` task on the 1.3 bench application (legacy image).
 * Expects bench/.legacy/app/vendor to link to bench/.legacy/vendor, dumped with `composer dump-autoload -o`.
 */

$root = dirname(__DIR__);

require $root . '/.legacy/vendor/autoload.php';

spl_autoload_register(function ($class) use ($root) {
    if (strpos($class, 'Bench\\') === 0) {
        $file = $root . '/.legacy/app/app/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

\Neutrino\Dotconst::load($root . '/.legacy/app');
@mkdir(BASE_PATH . '/bootstrap/compile', 0777, true);

$bootstrap = new \Neutrino\Foundation\Bootstrap(\Neutrino\Config\Loader::load(BASE_PATH));
$kernel = $bootstrap->make(\Bench\Kernels\CliKernel::class);
$kernel->boot();
$kernel->handle(['task' => \Neutrino\Foundation\Cli\Tasks\OptimizeTask::class]);
