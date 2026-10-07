<?php

declare(strict_types=1);

namespace Test\Rector;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The Rector configuration given to the applications (resources/rector/upgrade-2.0.php), on a 1.3 project:
 * fixtures/before, upgraded, gives fixtures/after (reviewed by hand).
 */
final class UpgradeConfigTest extends TestCase
{
    public function testUpgrade(): void
    {
        $root = dirname(__DIR__, 3);
        $dir = sys_get_temp_dir() . '/nucleon-rector-' . getmypid();
        exec('rm -rf ' . escapeshellarg($dir) . ' && cp -R ' . escapeshellarg(__DIR__ . '/fixtures/before') . ' ' . escapeshellarg($dir));

        try {
            $process = proc_open(
                [PHP_BINARY, $root . '/vendor/bin/rector', 'process', $dir, '--config=' . $root . '/resources/rector/upgrade-2.0.php', '--no-progress-bar', '--no-diffs', '--clear-cache'],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                $root,
            );

            if (!is_resource($process)) {
                throw new RuntimeException('Cannot run Rector.');
            }

            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            $this->assertSame(0, proc_close($process), (string) $output);

            foreach (glob(__DIR__ . '/fixtures/after/*.php') ?: [] as $expected) {
                $this->assertFileEquals($expected, $dir . '/' . basename($expected), basename($expected));
            }
        } finally {
            exec('rm -rf ' . escapeshellarg($dir));
        }
    }
}
