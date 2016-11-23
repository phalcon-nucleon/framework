<?php

namespace Test\TestCase;

use Neutrino\Test\FuncTestCase;

/**
 * Class TestCase
 */
abstract class TestCase extends FuncTestCase
{
    use TraitTestCase;

    public static $cache_dir = __DIR__ . '/../../.data/';

    public function setUp()
    {
        if (!is_dir(__DIR__ . '/../../.data/')) {
            if (!mkdir(__DIR__ . '/../../.data/')) {
                throw new \RuntimeException("Can't made .data directory.");
            }
        }

        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();

        $dir = __DIR__ . '/../../.data/';

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            clearstatcache(true, $dir . $item);
            @unlink($dir . $item);
        }

        clearstatcache(true);
        @rmdir($dir);
    }
}
