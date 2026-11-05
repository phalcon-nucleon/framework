<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Http\Kernel as HttpKernel;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\View\Engines\EngineRegister;
use Neutrino\View\Engines\Volt\VoltEngineRegister;
use Phalcon\Config\Config;
use Phalcon\Di\DiInterface;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionProperty;
use RuntimeException;
use SplFileInfo;
use Throwable;

/**
 * Compiles every `*.volt` template of `view.views_dir`: the first requests do not compile, and the production can
 * run with `view.options.stat = false`.
 *
 * The templates are compiled with the services of the HTTP kernel (a container of its own, with its providers):
 * Volt compiles `assets.x` to `$this->assets->x` only when `assets` is a service at compile time, and to an undefined
 * local variable otherwise. The kernel: `--kernel`, else `view.kernel`, else `App\Kernels\Http\Kernel` if it exists.
 */
final class ViewCacheTask extends Task
{
    /**
     * The HTTP kernel of the Nucleon skeleton.
     */
    public const string DEFAULT_KERNEL = 'App\\Kernels\\Http\\Kernel';

    #[Description('Compile all the Volt templates.')]
    #[Option('--kernel={class}', 'HTTP kernel whose services the templates use (default: view.kernel, else App\\Kernels\\Http\\Kernel).')]
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
        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);
        $di = $this->container($config);
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

    /**
     * The container of the HTTP kernel: its providers, registered without being built. The console container when
     * there is no HTTP kernel.
     */
    private function container(Config $config): DiInterface
    {
        $kernel = $this->getOption('kernel') ?? $config->path('view.kernel');

        if ($kernel === null) {
            if (!class_exists(self::DEFAULT_KERNEL)) {
                return $this->getDI();
            }

            $kernel = self::DEFAULT_KERNEL;
        }

        if (!is_string($kernel) || !is_subclass_of($kernel, HttpKernel::class)) {
            throw new RuntimeException('view:cache: the kernel "' . (is_scalar($kernel) ? $kernel : get_debug_type($kernel)) . '" is not a class extending ' . HttpKernel::class . '.');
        }

        $app = new $kernel();
        $diClass = (new ReflectionProperty($app, 'dependencyInjection'))->getValue($app);
        $diClass = is_string($diClass) && is_subclass_of($diClass, DiInterface::class) ? $diClass : FactoryDefault::class;

        /** @var DiInterface $di */
        $di = new $diClass();
        $di->setShared(Services::CONFIG, $config);
        ProviderRegistrar::register($di, $app->getProviders());

        return $di;
    }
}
