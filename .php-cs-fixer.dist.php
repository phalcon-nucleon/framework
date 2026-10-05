<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

/*
 * Only files already ported to 2.0 (they declare strict_types) are checked,
 * so that the 1.3 code still waiting for its epic does not fail the build.
 */
$finder = Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/bench', __DIR__ . '/bin'])
    ->exclude('Debug/resources')
    ->ignoreDotFiles(false)
    ->exclude('.legacy')
    ->name(['*.php', 'phpunit-migrated'])
    ->filter(static fn(SplFileInfo $file): bool => preg_match(
        '/^<\\?php\\s+declare\\(strict_types=1\\);/',
        ltrim((string) preg_replace('~^#!.*\\n~', '', (string) file_get_contents($file->getPathname()))),
    ) === 1);

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder);
