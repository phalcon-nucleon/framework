<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Bootstrap;
use Neutrino\Interfaces\Kernelable;
use Neutrino\Support\Facades\Facade;
use Neutrino\Support\IdeHelper\Generator;
use Phalcon\Config\Config;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Throwable;

/**
 * Generates `_ide_helper.php` and `.phpstorm.meta.php` from the services of the application.
 *
 * The services of the console kernel are always documented; the other kernels (HTTP, Micro) are given by
 * `--kernel` or by the `ide_helper.kernels` config, and the application Facades by `ide_helper.facades`.
 */
final class IdeHelperTask extends Task
{
    #[Description('Generate the IDE helpers (_ide_helper.php, .phpstorm.meta.php) of the services and Facades.')]
    #[Option('--kernel={class}', 'Kernel to document, in addition to the console one (comma separated; default: config ide_helper.kernels).')]
    #[Option('--output-dir={dir}', 'Directory of the generated files (default: the application directory).')]
    #[Option('--no-meta', 'Do not generate .phpstorm.meta.php.')]
    public function mainAction(): void
    {
        $cli = $this->getDI();
        /** @var Config $config */
        $config = $cli->getShared(Services::CONFIG);

        $containers = [$cli];

        try {
            foreach ($this->kernels($config) as $kernel) {
                $containers[] = self::boot($kernel, $config, $cli);
            }
        } catch (Throwable $e) {
            $this->block(['Cannot boot a kernel: ' . $e->getMessage()], 'error');

            return;
        }

        /** @var list<class-string<Facade>> $facades */
        $facades = array_values(array_unique([...Generator::FACADES, ...self::strings($config->path('ide_helper.facades'))]));
        $generator = new Generator($containers, $facades);

        $directory = $this->getOption('output-dir', BASE_PATH);
        $directory = is_string($directory) && $directory !== '' ? rtrim($directory, '/') : BASE_PATH;

        $files = $this->hasOption('no-meta')
            ? [$generator->writeIdeHelper($directory)]
            : $generator->write($directory);

        foreach ($files as $file) {
            $this->info('Generated ' . $file);
        }
        $this->line(sprintf('%d services documented.', count($generator->services())));
    }

    /**
     * @return list<class-string<Kernelable>>
     */
    private function kernels(Config $config): array
    {
        $option = $this->getOption('kernel');

        /** @var list<class-string<Kernelable>> */
        return is_string($option) && $option !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $option))))
            : self::strings($config->path('ide_helper.kernels'));
    }

    /**
     * Boots a kernel in its own container, then restores the console one.
     *
     * @param class-string<Kernelable> $kernel
     */
    private static function boot(string $kernel, Config $config, DiInterface $cli): DiInterface
    {
        try {
            $instance = (new Bootstrap($config))->make($kernel);

            if (!$instance instanceof InjectionAwareInterface) {
                throw new \UnexpectedValueException("$kernel has no container.");
            }

            return $instance->getDI();
        } finally {
            Di::setDefault($cli);
            Facade::clearResolvedInstances();
            Facade::setDependencyInjection($cli);
        }
    }

    /**
     * @return list<string>
     */
    private static function strings(mixed $value): array
    {
        if ($value instanceof Config) {
            $value = $value->toArray();
        }

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }
}
