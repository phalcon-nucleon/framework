<?php

declare(strict_types=1);

namespace Test\TestCase;

use Fake\Kernels\Http\StubKernelHttp;

/**
 * Boots the fake app's HTTP kernel with a minimal configuration.
 */
trait TraitTestCase
{
    protected static function kernelClassInstance(): string
    {
        return StubKernelHttp::class;
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // The cache stores are registered, not built: their format is ported with the cache (E7).
        self::setConfig([
            'cache' => [
                'stores'  => ['memory' => ['driver' => 'Memory', 'adapter' => 'None']],
                'default' => 'memory',
            ],
            'app'   => ['base_uri' => '/'],
        ]);
    }
}
