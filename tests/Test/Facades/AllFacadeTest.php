<?php

namespace Test\Facades;

use Neutrino\Constants\Services;
use Neutrino\Support\Facades\Auth;
use Neutrino\Support\Facades\Cache;
use Neutrino\Support\Facades\Flash;
use Neutrino\Support\Facades\Log;
use Neutrino\Support\Facades\Request;
use Neutrino\Support\Facades\Response;
use Neutrino\Support\Facades\Router;
use Neutrino\Support\Facades\Session;
use Neutrino\Support\Facades\Url;
use Neutrino\Support\Facades\View;
use Neutrino\Support\Reflacker;
use Test\TestCase\TestCase;

/**
 * Class AllFacadeTest
 *
 * @package Test\Facades
 */
class AllFacadeTest extends TestCase
{

    public function dataFacade()
    {
        return [
            [Auth::class, Services::AUTH],
            [Cache::class, Services::CACHE],
            [Flash::class, Services::FLASH],
            [Log::class, Services::LOGGER],
            [Request::class, Services::REQUEST],
            [Response::class, Services::RESPONSE],
            [Router::class, Services::ROUTER],
            [Session::class, Services::SESSION],
            [Url::class, Services::URL],
            [View::class, Services::VIEW],
        ];
    }

    /**
     * @dataProvider dataFacade
     *
     * @param $facadeClass
     * @param $serviceName
     */
    public function testFacade($facadeClass, $serviceName)
    {
        $this->assertEquals(
            $serviceName,
            Reflacker::invoke($facadeClass, 'getFacadeAccessor')
        );
    }
}
