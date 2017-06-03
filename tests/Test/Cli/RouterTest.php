<?php
namespace Test\Cli;

use Neutrino\Cli\Router;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Cli\Tasks\ListTask;
use Test\Stub\StubKernelCli;
use Test\TestCase\TestCase;

class RouterTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function dataAddTask()
    {
        return [
            ['task', ListTask::class, null, [],
             'task',
             ['task' => ListTask::class, 'action' => null]
            ],
            ['task', ListTask::class, 'action', [],
             'task',
             ['task' => ListTask::class, 'action' => 'action']]
            ,
            ['task {param}', ListTask::class, 'action', [],
             'task ([[:alnum:]]+)',
             ['task' => ListTask::class, 'action' => 'action', 'param' => 1]
            ],
        ];
    }

    /**
     * @dataProvider dataAddTask
     */
    public function testAddTask($pattern, $class, $action, $params, $expectedPattern, $expectedPaths)
    {
        /** @var Router $router */
        $router = $this->getDI()->getShared(Services::ROUTER);

        $route = $router->addTask($pattern, $class, $action, $params);

        $this->assertEquals($route->getPattern(), $expectedPattern);
        $this->assertEquals($route->getPaths(), $expectedPaths);
    }
}
