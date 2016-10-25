<?php

namespace Test\TestCase;

use Test\Stub\StubKernelHttp;

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
        global $config;

        $config = array_merge($config, [
            'cache'       => [],
            'application' => ['baseUri' => '/']
        ]);

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        global $config;

        $config = [];
    }

}
