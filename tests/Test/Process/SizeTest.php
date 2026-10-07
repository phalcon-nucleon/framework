<?php

declare(strict_types=1);

namespace Test\Process;

use PHPUnit\Framework\TestCase;

/**
 * The process component stays light: at most 400 lines, comments and blank lines excluded.
 */
final class SizeTest extends TestCase
{
    public function testLinesOfCode(): void
    {
        $lines = 0;

        foreach (glob(dirname(__DIR__, 3) . '/src/Neutrino/Process/{,*/}*.php', GLOB_BRACE) ?: [] as $file) {
            $code = '';
            foreach (token_get_all((string) file_get_contents($file)) as $token) {
                if (!is_array($token) || ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT)) {
                    $code .= is_array($token) ? $token[1] : $token;
                }
            }

            $lines += count(array_filter(explode("\n", $code), static fn(string $line): bool => trim($line) !== ''));
        }

        $this->assertGreaterThan(0, $lines);
        $this->assertLessThanOrEqual(400, $lines, 'src/Neutrino/Process: ' . $lines . ' lines of code.');
    }
}
