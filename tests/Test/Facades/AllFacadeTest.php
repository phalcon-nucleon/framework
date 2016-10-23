<?php

namespace Test\Facades;

use Luxury\Constants\Services;
use Luxury\Support\Facades\Auth;
use Luxury\Support\Facades\Cache;
use Luxury\Support\Facades\Flash;
use Luxury\Support\Facades\HttpClient;
use Luxury\Support\Facades\Log;
use Luxury\Support\Facades\Request;
use Luxury\Support\Facades\Response;
use Luxury\Support\Facades\Router;
use Luxury\Support\Facades\Session;
use Luxury\Support\Facades\Url;
use Luxury\Support\Facades\View;
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
            [HttpClient::class, Services::HTTP_CLIENT],
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
            $this->getPrivateMethod($facadeClass, 'getFacadeAccessor')
                ->invoke($facadeClass, 'getFacadeAccessor')
        );
    }
}
