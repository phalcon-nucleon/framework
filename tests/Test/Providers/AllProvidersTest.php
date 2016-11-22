<?php

namespace Test\Providers;

use Luxury\Auth\Manager as AuthManager;
use Luxury\Cache\CacheStrategy;
use Luxury\Constants\Services;
use Luxury\Interfaces\Providable;
use Luxury\Providers\Annotations;
use Luxury\Providers\Auth;
use Luxury\Providers\Cache;
use Luxury\Providers\Cookies;
use Luxury\Providers\Crypt;
use Luxury\Providers\Escaper;
use Luxury\Providers\Filter;
use Luxury\Providers\Flash;
use Luxury\Providers\Model;
use Luxury\Providers\ModelsMetaData;
use Luxury\Providers\Provider;
use Luxury\Providers\Security;
use Luxury\Providers\Url;
use Luxury\Providers\View;
use Phalcon\Annotations\Adapter\Memory as AnnotationsAdapterMemory;
use Phalcon\Flash\Direct as FlashDirect;
use Phalcon\Flash\Session as FlashSession;
use Phalcon\Mvc\Model\Manager as ModelManager;
use Phalcon\Mvc\Model\Metadata\Memory as ModelMetadataMemory;
use Phalcon\Mvc\Model\Transaction\Manager as ModelTransactionManager;
use Test\TestCase\TestCase;

/**
 * Class AllProvidersTest
 *
 * @package     Test\Providers
 */
class AllProvidersTest extends TestCase
{

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            Services::ANNOTATIONS => [Annotations::class, [
                [
                    'name'   => Services::ANNOTATIONS,
                    'class'  => AnnotationsAdapterMemory::class,
                    'shared' => true
                ]
            ]],
            
            Services::AUTH => [Auth::class, [
                [
                    'name'   => Services::AUTH,
                    'class'  => AuthManager::class,
                    'shared' => true
                ]
            ]],

            Services::CACHE => [Cache::class, [
                [
                    'name'   => Services::CACHE,
                    'class'  => CacheStrategy::class,
                    'shared' => true
                ]
            ]],

            Services::COOKIES => [Cookies::class, [
                [
                    'name'   => Services::COOKIES,
                    'class'  => \Phalcon\Http\Response\Cookies::class,
                    'shared' => true
                ]
            ]],

            Services::CRYPT => [Crypt::class, [
                [
                    'name'   => Services::CRYPT,
                    'class'  => \Phalcon\Crypt::class,
                    'shared' => true
                ]
            ]],

            Services::ESCAPER => [Escaper::class, [
                [
                    'name'   => Services::ESCAPER,
                    'class'  => \Phalcon\Escaper::class,
                    'shared' => true
                ]
            ]],

            Services::FILTER => [Filter::class, [
                [
                    'name'   => Services::FILTER,
                    'class'  => \Phalcon\Filter::class,
                    'shared' => true
                ]
            ]],

            Services::FLASH => [Flash::class, [
                [
                    'name'   => Services::FLASH,
                    'class'  => FlashDirect::class,
                    'shared' => false
                ]
            ]],

            Services::FLASH_SESSION => [\Luxury\Providers\FlashSession::class, [
                [
                    'name'   => Services::FLASH_SESSION,
                    'class'  => FlashSession::class,
                    'shared' => true
                ]
            ]],

            'Model' => [Model::class, [
                [
                    'name'   => Services::MODELS_MANAGER,
                    'class'  => ModelManager::class,
                    'shared' => true
                ], [
                    'name'   => Services::MODELS_METADATA,
                    'class'  => ModelMetadataMemory::class,
                    'shared' => true
                ], [
                    'name'   => Services::TRANSACTION_MANAGER,
                    'class'  => ModelTransactionManager::class,
                    'shared' => true
                ]
            ]],

            Services::MODELS_MANAGER => [\Luxury\Providers\ModelManager::class, [
                [
                    'name'   => Services::MODELS_MANAGER,
                    'class'  => ModelManager::class,
                    'shared' => true
                ]
            ]],

            Services::MODELS_METADATA => [ModelsMetaData::class, [
                [
                    'name'   => Services::MODELS_METADATA,
                    'class'  => ModelMetadataMemory::class,
                    'shared' => true
                ]
            ]],

            Services::TRANSACTION_MANAGER => [\Luxury\Providers\ModelTransactionManager::class, [
                [
                    'name'   => Services::TRANSACTION_MANAGER,
                    'class'  => ModelTransactionManager::class,
                    'shared' => true
                ]
            ]],

            Services::SECURITY => [Security::class, [
                [
                    'name'   => Services::SECURITY,
                    'class'  => \Phalcon\Security::class,
                    'shared' => true
                ]
            ]],

            Services::URL => [Url::class, [
                [
                    'name'   => Services::URL,
                    'class'  => \Phalcon\Mvc\Url::class,
                    'shared' => true
                ]
            ]],

            Services::VIEW => [View::class, [
                [
                    'name'   => Services::VIEW,
                    'class'  => \Phalcon\Mvc\View::class,
                    'shared' => true
                ], [
                    'name'   => Services::TAG,
                    'class'  => \Phalcon\Tag::class,
                    'shared' => true
                ], [
                    'name'   => Services::ASSETS,
                    'class'  => \Phalcon\Assets\Manager::class,
                    'shared' => true
                ]
            ]],
        ];
    }

    public function setUp()
    {
        global $config;

        $config = array_merge($config, [
            'cache'    => [],
            'log'      => [
                'adapter' => 'Multiple',
                'path'    => __DIR__ . '/../../.data/'
            ],
            'view'     => [
                'views_dir'     => '',
                'compiled_path' => ''
            ]
        ]);

        parent::setUp();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProviders($providerClass, $providedInstances)
    {
        /** @var Provider|Providable $provider */
        $provider = new $providerClass();

        $provider->registering();

        foreach ($providedInstances as $providedInstance) {
            $this->assertProvided(
                $providedInstance['name'],
                $providedInstance['class'],
                $providedInstance['shared']
            );
        }
    }

    /**
     * @param string $serviceName
     * @param string $instanceClass
     */
    public function assertProvided($serviceName, $instanceClass, $shared)
    {
        $this->assertTrue($this->getDI()->has($serviceName));


        $this->assertEquals($shared, $this->getDI()->getService($serviceName)->isShared());

        $this->assertInstanceOf(
            $instanceClass,
            $shared ? $this->getDI()->getShared($serviceName) : $this->getDI()->get($serviceName)
        );
    }
}
