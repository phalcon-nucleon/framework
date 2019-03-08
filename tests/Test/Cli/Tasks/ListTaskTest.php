<?php

namespace Test\Cli\Tasks;

use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Helper;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Debug\Reflexion;
use Neutrino\Foundation\Cli\Tasks\ListTask;
use Neutrino\Foundation\Cli\Tasks\OptimizeTask;
use Neutrino\Foundation\Cli\Tasks\RouteListTask;
use Phalcon\Cli\Dispatcher;
use Phalcon\Cli\Router\Route;
use Phalcon\Events\Manager;
use Test\TestCase\TestCase;

class ListTaskTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        Decorate::setColorSupport(true);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        Decorate::setColorSupport(null);
    }

    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function dataDescribe()
    {
        Decorate::setColorSupport(true);

        return [
            [[
                 'description' => 'List all commands available.',
                 'cmd'         => Decorate::info('list'),
             ], 'list', ListTask::class, 'mainAction'],
            [[
                 'description' => 'List all routes.',
                 'cmd'         => Decorate::info('route:list'),
                 'options'     => '--no-substitution: Doesn\'t replace matching group by params name',
             ], 'route:list', RouteListTask::class, 'mainAction'],
            [[
                 'description' => 'Runs all optimization.',
                 'cmd'         => Decorate::info('optimize'),
                 'options'     => '-m, --memory: Generate a memory optimized autoloader., -f, --force: Force optimization.',
             ], 'optimize', OptimizeTask::class, 'mainAction']
        ];
    }

    /**
     * @dataProvider dataDescribe
     */
    public function testDescribe($expected, $cmd, $class, $action)
    {
        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);

        $task = new ListTask();

        Reflexion::invoke($task, 'describe', $cmd, $class, $action);

        $describes = Reflexion::get($task, 'describes');

        $this->assertEquals([$expected], $describes);
    }

    public function dataDescribeRoute()
    {
        Decorate::setColorSupport(true);

        return [
            [[
                 'description' => 'List all commands available.',
                 'cmd'         => Decorate::info('list'),
             ], new Route('list', ['task' => ListTask::class])],
            [[
                 'description' => 'List all routes.',
                 'cmd'         => Decorate::info('route:list'),
                 'options'     => '--no-substitution: Doesn\'t replace matching group by params name',
             ], new Route('route:list', ['task' => RouteListTask::class])],
            [[
                 'description' => 'Runs all optimization.',
                 'cmd'         => Decorate::info('optimize'),
                 'options'     => '-m, --memory: Generate a memory optimized autoloader., -f, --force: Force optimization.',
             ], new Route('optimize', ['task' => OptimizeTask::class])]
        ];
    }

    /**
     * @dataProvider dataDescribeRoute
     */
    public function testDescribeRoute($expected, $route)
    {
        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');

        $task = new ListTask();

        Reflexion::invoke($task, 'describeRoute', $route);

        $describes = Reflexion::get($task, 'describes');

        $this->assertEquals([$expected], $describes);
    }

    public function testMainAction()
    {
        $expected = [
            'write'  => ['exactly' => 24, 'consecutive' => [
                [Helper::neutrinoVersion() . PHP_EOL, true],
                //['Usage :'],
                ['  command [options] [arguments]', true],
                ['', true],
                //['Options :'],
                //['  -h, --help                     Display this help message'],
                //['  -q, --quiet                    Do not output any message'],
                //['  -s, --stats                    Display timing and memory usage information'],
                //['      --colors                   Force Colors output'],
                //['      --no-colors                Disable Colors output'],
                ['', true],
                //['Available Commands :'],
                [' ' . Decorate::info('clear-compiled') . '         Clear compilation.                                    ', true],
                [' ' . Decorate::info('help ( .*)*') . '                                                                  ', true],
                [' ' . Decorate::info('list') . '                   List all commands available.                          ', true],
                [' ' . Decorate::info('migrate') . '                Run the database migrations.                          ', true],
                [' ' . Decorate::info('optimize') . '               Runs all optimization.                                ', true],
                // assets
                [' ' . Decorate::info('assets:js') . '              Compilation, Optimization, Minification of assets js. ', true],
                [' ' . Decorate::info('assets:sass') . '            Compilation des assets sass.                          ', true],
                //['config', true],
                [' ' . Decorate::info('config:cache') . '           Cache the configuration.                              ', true],
                [' ' . Decorate::info('config:clear') . '           Clear the configuration cache.                        ', true],
                //['make', true],
                [' ' . Decorate::info('make:migration ' . Decorate::notice('{name}')) . '  Create a new migration file.                          ', true],
                //['migrate', true],
                [' ' . Decorate::info('migrate:fresh') . '          Drop all tables and re-run all migrations.            ', true],
                [' ' . Decorate::info('migrate:install') . '        Create the migration storage.                         ', true],
                [' ' . Decorate::info('migrate:refresh') . '        Reset and re-run all migrations.                      ', true],
                [' ' . Decorate::info('migrate:reset') . '          Rollback all database migrations.                     ', true],
                [' ' . Decorate::info('migrate:rollback') . '       Rollback the last database migration.                 ', true],
                [' ' . Decorate::info('migrate:status') . '         Show the status of each migration.                    ', true],
                //['route', true],
                [' ' . Decorate::info('route:cache') . '            Generate a cache for http kernel\'s routes.            ', true],
                [' ' . Decorate::info('route:list') . '             List all routes.                                      ', true],
                //['server', true],
                [' ' . Decorate::info('server:run') . '             Runs a local web server                               ', true],
                //['view', true],
                [' ' . Decorate::info('view:clear') . '             Clear all compiled view files.                        ', true],
            ]],
            'notice' => ['exactly' => 10, 'consecutive' => [
                ['Usage :'],
                ['Options :'],
                ['Available Commands :'],
                ['assets'],
                ['config'],
                ['make'],
                ['migrate'],
                ['route'],
                ['server'],
                ['view'],
            ]],
            'info' => ['exactly' => 5, 'consecutive' => [
                ['  -h, --help                     Display this help message'],
                ['  -q, --quiet                    Do not output any message'],
                ['  -s, --stats                    Display timing and memory usage information'],
                ['      --colors                   Force Colors output'],
                ['      --no-colors                Disable Colors output'],
            ]]
        ];

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($this->createMock(Manager::class));
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');

        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        foreach ($expected as $func => $params) {
            $method = $mock->expects($this->exactly($params['exactly']))->method($func);

            if (!empty($params['consecutive'])) {
                $method->withConsecutive(...$params['consecutive']);
            }
        }

        $task = new ListTask();

        $task->mainAction();
    }
}
