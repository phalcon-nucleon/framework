<?php

declare(strict_types=1);

namespace Test\Debug;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Real HTTP requests on a kernel of their own (fixtures/http.php), run with php-cgi: the debug mode is only
 * registered outside the console.
 */
final class DebuggerHttpTest extends TestCase
{
    private static string $cgi = '';

    public static function setUpBeforeClass(): void
    {
        self::$cgi = dirname(PHP_BINARY) . '/php-cgi';
    }

    protected function setUp(): void
    {
        if (!is_executable(self::$cgi)) {
            $this->markTestSkipped('php-cgi is not installed next to ' . PHP_BINARY . '.');
        }
    }

    public function testDebugBarCollectsTheServicesOfTheRequest(): void
    {
        [$headers, $body] = self::request('/', true);

        $this->assertMatchesRegularExpression('/^X-Debug-Bar: \d+\r?$/m', $headers);
        $this->assertStringContainsString('<p>the page</p>', $body);
        $this->assertStringContainsString('id="phalcon-debugbar"', $body);
        // Database, cache and logger panels.
        $this->assertStringContainsString('nucleon_query', $body);
        $this->assertStringContainsString('nucleon_cache_key', $body);
        $this->assertStringContainsString('nucleon log message', $body);
    }

    public function testNothingLoadedWithoutDebug(): void
    {
        [$headers, $body] = self::request('/', false);

        $this->assertStringNotContainsString('X-Debug-Bar', $headers);
        $this->assertSame("<!DOCTYPE html><html><head></head><body><p>the page</p></body></html>\n<!-- debugger loaded: no -->", $body);
    }

    public function testDebugErrorPage(): void
    {
        [$headers, $body] = self::request('/fail', true);

        $this->assertStringContainsString('Status: 500 Internal Server Error', $headers);
        $this->assertStringContainsString('<h1>RuntimeException</h1>', $body);
        $this->assertStringContainsString('nucleon failure', $body);
        $this->assertStringContainsString('Previous exception #1', $body);
        $this->assertStringContainsString('<h1>LogicException</h1>', $body);
        $this->assertStringContainsString('class="hl-gutter hl-current"', $body);
    }

    public function testErrorPageWithoutDebug(): void
    {
        [$headers, $body] = self::request('/fail', false);

        $this->assertStringContainsString('Status: 500 Internal Server Error', $headers);
        // The uncaught exception ends the script.
        $this->assertSame('Whoops. Something went wrong.', $body);
    }

    /**
     * @return array{string, string} headers and body
     */
    private static function request(string $uri, bool $debug): array
    {
        $env = [
            'NUCLEON_DEBUG'   => $debug ? '1' : '0',
            'REQUEST_METHOD'  => 'GET',
            'REQUEST_URI'     => $uri,
            'SCRIPT_FILENAME' => __DIR__ . '/fixtures/http.php',
            'PATH'            => (string) getenv('PATH'),
        ];

        if (($scan = getenv('PHP_INI_SCAN_DIR')) !== false) {
            $env['PHP_INI_SCAN_DIR'] = $scan;
        }

        $process = proc_open([self::$cgi, '-d', 'cgi.force_redirect=0'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);

        if (!is_resource($process)) {
            throw new RuntimeException('Cannot run php-cgi.');
        }

        $output = (string) stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        proc_close($process);

        $parts = explode("\r\n\r\n", $output, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
