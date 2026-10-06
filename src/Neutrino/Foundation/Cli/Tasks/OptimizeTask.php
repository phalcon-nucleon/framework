<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Optimize\PreloadGenerator;
use Phalcon\Config\Config;
use Throwable;

final class OptimizeTask extends Task
{
    private const array COMPILE_TASKS = [
        ConfigCacheTask::class,
        DotconstCacheTask::class,
        RouteCacheTask::class,
        ViewCacheTask::class,
    ];

    #[Description('Runs all optimizations: authoritative Composer classmap, configuration, dotconst and routes caches, compiled views, OPcache preload script.')]
    #[Option('-f, --force', 'Force optimization in debug mode.')]
    #[Option('--no-dump', 'Do not run `composer dump-autoload`.')]
    #[Option('--apcu', 'Use APCu to cache the Composer class lookups.')]
    #[Option('--no-dev', 'Exclude require-dev packages from the autoloader.')]
    #[Option('--composer={path}', 'Composer binary (default: composer).')]
    #[Option('--no-preload', 'Do not generate the OPcache preload script.')]
    public function mainAction(): void
    {
        if (APP_DEBUG && !$this->hasOption('f', 'force')) {
            $this->info('Application is in debug mode.');
            $this->info('For optimize in debug please use the --force, -f option.');

            return;
        }

        if (!$this->hasOption('no-dump') && !$this->dumpAutoload()) {
            return;
        }

        foreach (self::COMPILE_TASKS as $task) {
            $this->callTask($task, 'main');
        }

        if (!$this->hasOption('no-preload')) {
            $this->generatePreload();
        }
    }

    private function dumpAutoload(): bool
    {
        $this->writer()->write(Decorate::notice(str_pad('Generating authoritative classmap', 40)), false);

        $composer = $this->getOption('composer', 'composer');
        $command = [is_string($composer) ? $composer : 'composer', 'dump-autoload', '--classmap-authoritative', '--quiet'];
        if ($this->hasOption('apcu')) {
            $command[] = '--apcu';
        }
        if ($this->hasOption('no-dev')) {
            $command[] = '--no-dev';
        }

        $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
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

    private function generatePreload(): void
    {
        $this->writer()->write(Decorate::notice(str_pad('Generating OPcache preload script', 40)), false);

        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);
        $preload = $config->path('optimize.preload');
        $preload = $preload instanceof Config ? $preload->toArray() : [];

        try {
            /** @var list<string> $namespaces */
            $namespaces = $preload['namespaces'] ?? ['Neutrino\\'];
            /** @var list<string>|null $paths */
            $paths = $preload['paths'] ?? null;
            /** @var list<string> $excludes */
            $excludes = $preload['excludes'] ?? PreloadGenerator::DEFAULT_EXCLUDES;

            $result = (new PreloadGenerator(BASE_PATH, BASE_PATH . '/vendor', $namespaces, $paths, $excludes))->generate();
        } catch (Throwable $e) {
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
