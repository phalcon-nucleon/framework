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

        self::setConfig([
            'cache' => [
                'stores'  => ['memory' => ['adapter' => 'memory']],
                'default' => 'memory',
            ],
            'app'   => ['base_uri' => '/'],
        ]);
    }
}
