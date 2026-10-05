<?php

declare(strict_types=1);

namespace Test\Cli;

use Fake\Kernels\Cli\Tasks\StubTask;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Constants\Services;

final class KernelCliTest extends CliTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        StubTask::$calls = [];
    }

    public function testTaskReceivesItsArgumentsAndOptions(): void
    {
        $this->app->handle(['task' => StubTask::class, 'action' => 'test', 'first', 'second', '--name=value', '-f']);

        $this->assertSame([['testAction', ['first', 'second'], ['name' => 'value', 'f' => true]]], StubTask::$calls);
    }

    public function testRouteParameters(): void
    {
        /** @var \Neutrino\Cli\Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);
        $router->addTask('stub:make {name} {type}', StubTask::class, 'test');

        $this->runCommand('stub:make users table --force');

        $this->assertSame([['testAction', ['name' => 'users', 'type' => 'table'], ['force' => true]]], StubTask::$calls);
    }

    public function testHandleIncomingReadsArgv(): void
    {
        $argv = $_SERVER['argv'] ?? null;
        $_SERVER['argv'] = ['nucleon', 'list'];

        try {
            $this->app->handleIncoming();
        } finally {
            $_SERVER['argv'] = $argv;
        }

        $this->assertStringContainsString('Available Commands :', $this->output->out);
    }

    public function testGlobalOptions(): void
    {
        $this->app->setArgument(['nucleon', 'list', '-q', '--stats', '--no-colors']);

        $this->assertTrue($this->app->isQuiet());
        $this->assertTrue($this->app->withStats());
        $this->assertFalse($this->app->isHelp());
        $this->assertSame(['list'], $this->app->getArguments());

        Decorate::setColorSupport(true);
        $this->app->boot();
        $this->assertFalse(Decorate::hasColorSupport());

        $this->app->setArgument(['nucleon', 'list', '--colors']);
        $this->app->boot();
        $this->assertTrue(Decorate::hasColorSupport());
    }

    public function testStats(): void
    {
        $this->runCommand('list --stats');
        $this->app->terminate();

        $this->assertMatchesRegularExpression("/Stats : \n\tmem:\d+\n\tmem.peak:\d+\n\ttime:[\d.E-]+/", $this->output->out);
    }

    public function testQuietOutput(): void
    {
        $this->app->setArgument(['nucleon', 'list', '--quiet']);
        $this->getDI()->remove(Services\Cli::OUTPUT);
        $this->app->registerServices();

        ob_start();
        try {
            /** @var \Neutrino\Cli\Output\Writer $output */
            $output = $this->getDI()->getShared(Services\Cli::OUTPUT);

            $output->info('silent');
            echo 'swallowed';
            $output->clean();
            $this->assertSame('', ob_get_contents());
        } finally {
            ob_end_clean();
        }
    }
}
