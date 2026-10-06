<?php

declare(strict_types=1);

namespace Test\HttpClient;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The HTTP client stays light: at most 1 500 lines, comments and blank lines excluded.
 */
final class SizeTest extends TestCase
{
    public function testLinesOfCode(): void
    {
        $lines = 0;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 3) . '/src/Neutrino/HttpClient', RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $code = '';
            foreach (token_get_all((string) file_get_contents((string) $file)) as $token) {
                if (!is_array($token) || ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT)) {
                    $code .= is_array($token) ? $token[1] : $token;
                }
            }

            $lines += count(array_filter(explode("\n", $code), static fn(string $line): bool => trim($line) !== ''));
        }

        $this->assertLessThanOrEqual(1500, $lines, 'src/Neutrino/HttpClient: ' . $lines . ' lines of code.');
    }
}
