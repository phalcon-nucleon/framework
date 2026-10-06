<?php

declare(strict_types=1);

namespace Test\Error\Writer;

use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer\Phplog;
use Test\TestCase\TestCase;

final class PhplogTest extends TestCase
{
    public function testHandle(): void
    {
        $file = self::$cache_dir . 'php.log';
        $previous = ini_set('error_log', $file);
        $error = Error::fromError(E_WARNING, 'msg', __FILE__, 1);

        try {
            (new Phplog())->handle($error);
        } finally {
            ini_set('error_log', (string) $previous);
        }

        $this->assertStringEndsWith('] ' . Helper::format($error) . "\n", (string) file_get_contents($file));
    }
}
