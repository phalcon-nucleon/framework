<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/*
 * Mechanical upgrades used during the 2.0 migration. Never applied in CI:
 * run it on a module, review the diff, then commit.
 *
 *   vendor/bin/rector process src/Neutrino/<Module> --dry-run
 */
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withSkip([__DIR__ . '/src/Neutrino/Debug/resources'])
    ->withPhpSets(php83: true)
    // PHPUnit upgrade rules matching the installed PHPUnit version.
    ->withComposerBased(phpunit: true);
