<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Foundation\Optimize\PreloadGenerator;

/**
 * Class OptimizeTask
 *
 * @package Neutrino\Foundation\Cli
 */
class OptimizeTask extends Task
{
    private $compileTasks = [
        ConfigCacheTask::class,
        DotconstCacheTask::class,
        RouteCacheTask::class
    ];

    /**
     * Runs all optimizations: authoritative Composer classmap, configuration,
     * dotconst and routes caches, and the OPcache preload script.
     *
     * @description Runs all optimizations.
     *
     * @option      -f, --force: Force optimization in debug mode.
     * @option      --no-dump: Do not run `composer dump-autoload`.
     * @option      --apcu: Use APCu to cache the Composer class lookups.
     * @option      --no-dev: Exclude require-dev packages from the autoloader.
     * @option      --composer={path}: Composer binary (default: composer).
     * @option      --no-preload: Do not generate the OPcache preload script.
     */
    public function mainAction()
    {
        if (APP_DEBUG && !$this->hasOption('f', 'force')) {
            $this->info('Application is in debug mode.');
            $this->info('For optimize in debug please use the --force, -f option.');

            return;
        }

        if (!$this->hasOption('no-dump') && !$this->dumpAutoload()) {
            return;
        }

        foreach ($this->compileTasks as $compileTask) {
            $this->application->handle([
                'task' => $compileTask
            ]);
        }

        if (!$this->hasOption('no-preload')) {
            $this->generatePreload();
        }
    }

    private function dumpAutoload()
    {
        $this->output->write(Decorate::notice(str_pad('Generating authoritative classmap', 40, ' ')), false);

        $command = [$this->getOption('composer', 'composer'), 'dump-autoload', '--classmap-authoritative', '--quiet'];
        if ($this->hasOption('apcu')) {
            $command[] = '--apcu';
        }
        if ($this->hasOption('no-dev')) {
            $command[] = '--no-dev';
        }

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        if ($process === false) {
            $this->error('Error');
            $this->block(['Cannot run: ' . implode(' ', $command)], 'error');

            return false;
        }

        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        if (proc_close($process) !== 0) {
            $this->error('Error');
            $this->block([implode(' ', $command) . ' failed:', trim($output)], 'error');

            return false;
        }

        $this->info('Success');

        return true;
    }

    private function generatePreload()
    {
        $this->output->write(Decorate::notice(str_pad('Generating OPcache preload script', 40, ' ')), false);

        $config = isset($this->config->optimize->preload) ? $this->config->optimize->preload->toArray() : [];

        try {
            $result = (new PreloadGenerator(
                BASE_PATH,
                BASE_PATH . '/vendor',
                isset($config['namespaces']) ? $config['namespaces'] : ['Neutrino\\'],
                isset($config['paths']) ? $config['paths'] : null,
                isset($config['excludes']) ? $config['excludes'] : PreloadGenerator::DEFAULT_EXCLUDES
            ))->generate();
        } catch (\Exception $e) {
            $this->error('Error');
            $this->block([$e->getMessage()], 'error');

            return;
        }

        $this->info('Success');
        $this->line(sprintf('  %d classes preloaded, %d skipped.', count($result->preloaded), count($result->skipped)));

        foreach ($result->skipped as $class => $reason) {
            $this->warn("  skipped $class: $reason");
        }

        $this->line('');
        $this->line('Recommended php.ini settings for production:');
        $this->line('  opcache.enable = 1');
        $this->line('  opcache.validate_timestamps = 0');
        $this->line('  opcache.preload = ' . $result->file);
        $this->line('  opcache.preload_user = <the PHP-FPM user>');
    }
}
