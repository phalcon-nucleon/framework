<?php

declare(strict_types=1);

namespace Test\Facades;

use Neutrino\Constants\Services;
use Neutrino\Support\Facades;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class AllFacadeTest extends TestCase
{
    /**
     * @return iterable<array{class-string<Facades\Facade>, string}>
     */
    public static function dataFacade(): iterable
    {
        yield [Facades\Auth::class, Services::AUTH];
        yield [Facades\Cache::class, Services::CACHE];
        yield [Facades\Flash::class, Services::FLASH];
        yield [Facades\Http::class, Services::HTTP_CLIENT];
        yield [Facades\Log::class, Services::LOGGER];
        yield [Facades\Request::class, Services::REQUEST];
        yield [Facades\Response::class, Services::RESPONSE];
        yield [Facades\Router::class, Services::ROUTER];
        yield [Facades\Micro\Router::class, Services::MICRO_ROUTER];
        yield [Facades\Session::class, Services::SESSION];
        yield [Facades\Url::class, Services::URL];
        yield [Facades\View::class, Services::VIEW];
    }

    /**
     * @param class-string<Facades\Facade> $facadeClass
     */
    #[DataProvider('dataFacade')]
    public function testFacadeAccessor(string $facadeClass, string $serviceName): void
    {
        $this->assertSame($serviceName, (new ReflectionMethod($facadeClass, 'getFacadeAccessor'))->invoke(null));
    }

    public function testEveryFacadeIsTested(): void
    {
        $tested = array_column(iterator_to_array(self::dataFacade(), false), 0);

        $facades = [];
        foreach (glob(dirname(__DIR__, 3) . '/src/Neutrino/Support/Facades/{,*/}*.php', GLOB_BRACE) ?: [] as $file) {
            $class = 'Neutrino\\Support\\Facades\\' . str_replace('/', '\\', substr($file, strpos($file, 'Facades/') + 8, -4));
            if ($class !== Facades\Facade::class) {
                $facades[] = $class;
            }
        }

        $this->assertEqualsCanonicalizing($facades, $tested);
    }
}
