<?php

declare(strict_types=1);

namespace Test\Assert;

use Neutrino\Constants\Services;
use Phalcon\Http\Response;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\SkippedTest;
use RuntimeException;
use stdClass;
use Test\TestCase\TestCase;

final class FuncTestCaseTest extends TestCase
{
    /**
     * @return iterable<string, array{string, array<string, string>, array<string, string>, array<string, string>}>
     */
    public static function dataDispatch(): iterable
    {
        foreach (['GET', 'HEAD', 'DELETE'] as $method) {
            yield $method => [$method, [], [], []];
            yield "$method.withParams" => [$method, ['data' => 'test'], ['data' => 'test'], []];
        }
        foreach (['POST', 'PUT', 'PATCH'] as $method) {
            yield $method => [$method, [], [], []];
            yield "$method.withParams" => [$method, ['data' => 'test'], [], ['data' => 'test']];
        }
    }

    /**
     * @param array<string, string> $params
     * @param array<string, string> $get
     * @param array<string, string> $post
     */
    #[DataProvider('dataDispatch')]
    public function testDispatchParameters(string $method, array $params, array $get, array $post): void
    {
        $this->addDataRoute($method);

        $output = $this->dispatch('/dispatch', $method, $params);

        // The content of the response, not sent: what the client receives.
        $this->assertSame($this->getContent(), $output);
        $this->assertInstanceOf(Response::class, $this->getDI()->getShared(Services::RESPONSE));

        $content = json_decode($this->getContent(), true);
        $this->assertSame($method, $content['method']);
        $this->assertSame($get, $content['get']);
        $this->assertSame($post, $content['post']);
    }

    public function testDispatchQueryStringHeadersAndJson(): void
    {
        $this->addDataRoute('POST');

        $this->dispatch('/dispatch?page=2', 'POST', ['a' => '1'], ['X-Test' => 'yes'], ['json' => true]);

        $content = json_decode($this->getContent(), true);
        $this->assertSame(['page' => '2'], $content['get']);
        $this->assertSame(['a' => '1'], $content['post']);
        $this->assertSame(['X-Test' => 'yes', 'Content-Type' => 'application/json'], $content['headers']);
        $this->assertSame(['json' => true], $content['json']);
    }

    public function testDispatchRestoresTheSuperglobals(): void
    {
        $this->addDataRoute('POST');
        $server = $_SERVER;
        $_GET = ['kept' => 'get'];
        $_POST = ['kept' => 'post'];

        $this->dispatch('/dispatch?page=2', 'POST', ['a' => '1'], ['X-Test' => 'yes']);

        $this->assertSame($server, $_SERVER);
        $this->assertSame(['kept' => 'get'], $_GET);
        $this->assertSame(['kept' => 'post'], $_POST);
    }

    public function testDispatchReturnsTheOutput(): void
    {
        $router = $this->getDI()->getShared(Services::ROUTER);
        $this->assertInstanceOf(Router::class, $router);
        $router->addGet('/echo', ['namespace' => \Fake\Kernels\Http\Controllers::class, 'controller' => 'Stub', 'action' => 'return']);
        $this->app->getEventsManager()?->attach('application:beforeSendResponse', function (): void {
            echo 'echoed';
        });

        // The output, then the content of the response (the value returned by the action).
        $this->assertSame('echoed' . \Fake\Kernels\Http\Controllers\StubController::class . '::returnAction', $this->dispatch('/echo'));
    }

    public function testDispatchRethrowsAndCleansTheBuffer(): void
    {
        $level = ob_get_level();

        try {
            $this->dispatch('/unknown-controller/action');
            $this->fail('An exception was expected.');
        } catch (\Phalcon\Mvc\Dispatcher\Exception) {
            $this->assertSame($level, ob_get_level());
        }
    }

    public function testDispatchCliNeedsACliKernel(): void
    {
        $this->expectException(RuntimeException::class);

        $this->dispatchCli('nucleon list');
    }

    public function testMockService(): void
    {
        $mock = $this->mockService(Services::URL, \Phalcon\Mvc\Url::class);
        $this->assertSame($mock, $this->getDI()->getShared(Services::URL));

        $instance = new stdClass();
        $this->assertSame($instance, $this->mockService('custom', $instance, false));
        $this->assertSame($instance, $this->getDI()->get('custom'));
    }

    public function testAssertControllerAndAction(): void
    {
        $this->dispatch('/return');

        $this->assertController('Stub');
        $this->assertAction('return');
    }

    public function testAssertControllerFail(): void
    {
        $this->dispatch('/return');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting Controller name "Blablabla", actual Controller name is "Stub"');

        $this->assertController('Blablabla');
    }

    public function testAssertActionFail(): void
    {
        $this->dispatch('/return');

        $this->expectException(ExpectationFailedException::class);

        $this->assertAction('Blablabla');
    }

    public function testAssertResponseContentContains(): void
    {
        $this->dispatch('/return');

        $this->assertResponseContentContains('return');

        $this->expectException(ExpectationFailedException::class);
        $this->assertResponseContentContains('redirect');
    }

    public function testAssertRedirectTo(): void
    {
        $this->dispatch('/redirect');

        $this->assertRedirectTo('/');

        $this->expectException(ExpectationFailedException::class);
        $this->assertRedirectTo('/wrong');
    }

    public function testNoRedirect(): void
    {
        $this->dispatch('/return');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting response caused a redirect');

        $this->assertRedirectTo('/');
    }

    public function testAssertResponseCode(): void
    {
        $this->dispatch('/redirect');

        $this->assertResponseCode(302);
    }

    public function testAssertResponseCodeFail(): void
    {
        $this->dispatch('/return');

        $this->expectException(ExpectationFailedException::class);

        $this->assertResponseCode(302);
    }

    public function testAssertHeaders(): void
    {
        $this->dispatch('/redirect');

        $this->assertHeader(['Location' => '/']);

        $this->expectException(ExpectationFailedException::class);
        $this->assertHeader(['Location' => '/return']);
    }

    public function testAssertForwarded(): void
    {
        $this->dispatch('/forwarded');

        $this->assertDispatchIsForwarded();
    }

    public function testAssertForwardedFail(): void
    {
        $this->dispatch('/redirect');

        $this->expectException(ExpectationFailedException::class);

        $this->assertDispatchIsForwarded();
    }

    public function testCheckExtension(): void
    {
        $this->checkExtension('json');
        $this->checkExtension(['json', 'pcre']);

        try {
            $this->checkExtension(['json', 'phalconista']);
            $this->fail('The test was expected to be skipped.');
        } catch (SkippedTest $e) {
            $this->assertSame('Warning: phalconista extension is not loaded', $e->getMessage());
        }
    }

    public function testPhalconIsDetectedWithoutTheExtension(): void
    {
        self::assertPhalconIsAvailable();

        $this->addToAssertionCount(1);
    }

    public function testMissingPhalconFails(): void
    {
        // A PHP process without any extension (-n), hence without Phalcon. Composer's ClassLoader is used
        // directly: vendor/autoload.php would stop on the platform check (ext-phalcon).
        $vendor = var_export(dirname(__DIR__, 3) . '/vendor/composer/', true);
        $code = <<<PHP
            require $vendor . 'ClassLoader.php';
            \$loader = new Composer\\Autoload\\ClassLoader();
            \$loader->addClassMap(require $vendor . 'autoload_classmap.php');
            foreach (require $vendor . 'autoload_psr4.php' as \$prefix => \$dirs) { \$loader->setPsr4(\$prefix, \$dirs); }
            \$loader->register();
            try {
                Neutrino\\Test\\TestCase::assertPhalconIsAvailable();
                echo 'no failure';
            } catch (Throwable \$e) {
                echo \$e::class, ': ', \$e->getMessage();
            }
            PHP;

        $process = proc_open([PHP_BINARY, '-n', '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        $output = (string) stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        if (extension_loaded('phalcon')) {
            // The test case cannot even be loaded (it implements Phalcon interfaces): the run fails, nothing is skipped.
            $this->assertMatchesRegularExpression('/^(Error: Interface "Phalcon\\\\.+" not found|' . preg_quote(AssertionFailedError::class, '/') . ': Phalcon is not available)/', $output);
        } else {
            // Phalcon 6: the phalcon/phalcon package is found without any extension.
            $this->assertSame('no failure', $output);
        }
    }

    private function addDataRoute(string $method): void
    {
        $router = $this->getDI()->getShared(Services::ROUTER);
        $this->assertInstanceOf(Router::class, $router);

        $router->add('/dispatch', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stub',
            'action'     => 'data',
        ], [$method]);
    }
}
