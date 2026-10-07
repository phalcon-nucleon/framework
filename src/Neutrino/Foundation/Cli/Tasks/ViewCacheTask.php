<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\View\Engines\EngineRegister;
use Neutrino\View\Engines\Volt\VoltEngineRegister;
use Phalcon\Config\Config;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

/**
 * Compiles every `*.volt` template of `view.views_dir`: the first requests do not compile, and the production can
 * run with `view.options.stat = false`.
 */
final class ViewCacheTask extends Task
{
    #[Description('Compile all the Volt templates.')]
    public function mainAction(): void
    {
        $this->writer()->write(Decorate::notice(str_pad('Compiling views', 40)), false);

        try {
            $count = $this->compile();

            $this->info("Success ($count)");
        } catch (Throwable $e) {
            $this->error('Error');
            $this->block([$e->getMessage()], 'error');
        }
    }

    private function compile(): int
    {
        $di = $this->getDI();
        /** @var Config $config */
        $config = $di->getShared(Services::CONFIG);
        $directories = $config->path('view.views_dir');
        $directories = $directories instanceof Config ? $directories->toArray() : [$directories];

        $engines = $config->path('view.engines');
        $register = $engines instanceof Config ? $engines->get('.volt') : null;
        $register = is_string($register) && is_subclass_of($register, EngineRegister::class) ? $register : VoltEngineRegister::class;

        $view = new View();
        $view->setDI($di);
        // Volt resolves `{% extends %}` and `{% include %}` against the views directories of the view.
        $view->setViewsDir(array_values(array_filter($directories, is_string(...))));
        $engine = (new $register())->register($view, $di);

        if (!$engine instanceof Volt) {
            return 0;
        }

        $compiler = $engine->getCompiler();
        $compiler->setOption('always', true);
        $count = 0;

        foreach ($directories as $directory) {
            if (!is_string($directory) || !is_dir($directory)) {
                continue;
            }

            /** @var SplFileInfo $file */
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && $file->getExtension() === 'volt') {
                    $compiler->compile($file->getPathname());
                    $count++;
                }
            }
        }

        return $count;
    }
}
