<?php

namespace Test\TestCase;

use Fake\Kernels\Http\StubKernelHttp;

/**
 * Class TraitTestCase
 */
trait TraitTestCase
{
    /**
     * @return mixed
     */
    protected static function kernelClassInstance()
    {
        return StubKernelHttp::class;
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::setConfig([
            'cache'       => [
                'stores' => ['memory' => [
                    'driver' => \Phalcon\Cache\Backend\Memory::class,
                    'adapter' => 'None',
                ]],
                'default' => 'memory'
            ],
            'app' => ['base_uri' => '/']
        ]);
    }
}
