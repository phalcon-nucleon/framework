<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Task;
use Neutrino\Constants\Services;
use Phalcon\Config\Config;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\MetaData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Throwable;

/**
 * Writes the meta-data of every model of `models.paths` (default `app/Models`) in the meta-data cache.
 *
 * Useful with the `stream`, `redis` and `libmemcached` adapters, shared with the web server. APCu keeps a
 * cache per process family: the command line cannot warm the cache of PHP-FPM.
 */
final class ModelCacheTask extends Task
{
    #[Description('Cache the meta-data of the models.')]
    public function mainAction(): void
    {
        $this->writer()->write(Decorate::notice(str_pad('Caching models meta-data', 40)), false);

        try {
            $count = $this->cache();

            $this->info("Success ($count)");
        } catch (Throwable $e) {
            $this->error('Error');
            $this->block([$e->getMessage()], 'error');
        }
    }

    private function cache(): int
    {
        $di = $this->getDI();
        /** @var MetaData $metaData */
        $metaData = $di->getShared(Services::MODELS_METADATA);

        if ($metaData instanceof MetaData\Memory) {
            $this->warn('The meta-data adapter is "memory": nothing is kept between requests.');
        }

        $count = 0;

        foreach ($this->models() as $class) {
            $model = new $class();
            $metaData->readMetaData($model);
            $metaData->readColumnMap($model);
            $count++;
        }

        return $count;
    }

    /**
     * @return list<class-string<Model<mixed>>>
     */
    private function models(): array
    {
        /** @var Config $config */
        $config = $this->getDI()->getShared(Services::CONFIG);
        $paths = $config->path('models.paths');
        $paths = $paths instanceof Config ? $paths->toArray() : [BASE_PATH . '/app/Models'];
        $models = [];

        foreach ($paths as $path) {
            if (!is_string($path) || !is_dir($path)) {
                continue;
            }

            /** @var SplFileInfo $file */
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                $class = $file->getExtension() === 'php' ? self::className((string) file_get_contents($file->getPathname())) : null;

                if ($class !== null && class_exists($class) && is_subclass_of($class, Model::class) && (new ReflectionClass($class))->isInstantiable()) {
                    $models[] = $class;
                }
            }
        }

        return $models;
    }

    /**
     * The class declared by a PHP file.
     */
    private static function className(string $code): ?string
    {
        $namespace = '';
        $tokens = \PhpToken::tokenize($code);

        foreach ($tokens as $i => $token) {
            if ($token->is(T_NAMESPACE)) {
                for ($j = $i + 1; isset($tokens[$j]) && !$tokens[$j]->is([';', '{']); $j++) {
                    $namespace .= $tokens[$j]->is([T_NAME_QUALIFIED, T_STRING]) ? $tokens[$j]->text : '';
                }
            } elseif ($token->is(T_CLASS) && !($tokens[$i - 1] ?? null)?->is(T_DOUBLE_COLON)) {
                for ($j = $i + 1; isset($tokens[$j]); $j++) {
                    if ($tokens[$j]->is(T_STRING)) {
                        return ltrim($namespace . '\\' . $tokens[$j]->text, '\\');
                    }
                    if (!$tokens[$j]->isIgnorable()) {
                        return null; // anonymous class
                    }
                }
            }
        }

        return null;
    }
}
