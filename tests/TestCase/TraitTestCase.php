<?php

namespace TestCase;

use Stub\StubKernel;

/**
 * Class TraitTestCase
 */
trait TraitTestCase
{
    /**
     * @return mixed
     */
    protected function kernel()
    {
        return StubKernel::class;
    }
}