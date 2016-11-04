<?php
namespace Test\Cli;

use Luxury\Cli\Router;
use Luxury\Constants\Services;
use Luxury\Foundation\Cli\ClearCompiledTask;
use Luxury\Foundation\Cli\HelperTask;
use Luxury\Foundation\Cli\ListTask;
use Luxury\Foundation\Cli\OptimizeTask;
use Luxury\Foundation\Cli\RouteListTask;
use Luxury\Foundation\Cli\ViewClearTask;
use Test\Stub\StubKernelCli;
use Test\TestCase\TestCase;

class RouterTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function dataClassToTask()
    {
        return [
            [ClearCompiledTask::class, 'Luxury\Foundation\Cli\ClearCompiled'],
            [HelperTask::class, 'Luxury\Foundation\Cli\Helper'],
            [ListTask::class, 'Luxury\Foundation\Cli\List'],
            [OptimizeTask::class, 'Luxury\Foundation\Cli\Optimize'],
            [RouteListTask::class, 'Luxury\Foundation\Cli\RouteList'],
            [ViewClearTask::class, 'Luxury\Foundation\Cli\ViewClear'],
        ];
    }

    /**
     * @dataProvider dataClassToTask
     */
    public function testClassToTask($class, $expected)
    {
        $this->assertEquals($expected, Router::classToTask($class));
    }

    public function dataAddTask()
    {
        return [
            ['task', ListTask::class, null, [],
                'task',
                ['task' => Router::classToTask(ListTask::class), 'action' => null]
            ],
            ['task', ListTask::class, 'action', [],
                'task',
                ['task' => Router::classToTask(ListTask::class), 'action' => 'action']]
            ,
            ['task :param:', ListTask::class, 'action', [],
                'task ([[:alnum:]]+)',
                ['task' => Router::classToTask(ListTask::class), 'action' => 'action', 'param' => 1]
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
