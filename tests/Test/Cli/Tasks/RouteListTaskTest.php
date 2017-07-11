<?php

namespace Test\Cli\Tasks;

use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Cli\Tasks\RouteListTask;
use Phalcon\Cli\Dispatcher;
use Phalcon\Cli\Router;
use Phalcon\Events\Manager;
use Test\TestCase\TestCase;

class RouteListTaskTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelCli::class;
    }

    public function testGetHttpRoute()
    {
        $expectedRoutes = [
            ['pattern' => '/get', 'paths' => ['controller' => 'Stub', 'action' => 'index']]
        ];

        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');

        $task = new RouteListTask();

        /** @var Router\Route[] $routes */
        $routes = $this->invokeMethod($task, 'getHttpRoutes', []);

        $this->assertInstanceOf(Router::class, $this->getDI()->getShared(Services::ROUTER));

        foreach ($expectedRoutes as $key => $expectedRoute) {
            $route = $routes[$key];

            $this->assertEquals($expectedRoute['pattern'], $route->getPattern());
            $this->assertEquals($expectedRoute['paths'], $route->getPaths());
        }
    }

    public function testMainAction()
    {
        $expected = [
            'write' => ['exactly' => 9, 'consecutive' => [
                ['+--------+------+----------+-----------------------------+------------------------------------------------------+-------------------------------+'],
                ['| DOMAIN | NAME | METHOD   | PATTERN                     | ACTION                                               | MIDDLEWARE                    |'],
                ['+--------+------+----------+-----------------------------+------------------------------------------------------+-------------------------------+'],
                ['|        |      | GET      | /get                        | App\Http\Controllers\StubController::index           |                               |'],
                ['|        |      | POST     | /post                       | Fake\Kernels\Http\Controllers\StubController::index  |                               |'],
                ['|        |      | GET      | /u/'.Decorate::notice('{user}').'                   | Fake\Kernels\Http\Controllers\StubController::index  |                               |'],
                ['|        |      | GET|HEAD | /get-head                   | Fake\Kernels\Http\Controllers\StubController::index  | '.\Neutrino\Http\Middleware\Csrf::class.' |'],
                ['|        |      | GET      | /back/'.Decorate::notice('{controller}').'/'.Decorate::notice('{action}').' | Fake\Kernels\Http\Controllers\\'.Decorate::notice('{controller}').'::'.Decorate::notice('{action}').' |                               |'],
                ['+--------+------+----------+-----------------------------+------------------------------------------------------+-------------------------------+'],
            ]]
        ];

        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');

        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        foreach ($expected as $func => $params) {
            $method = $mock->expects($this->exactly($params['exactly']))
                ->method($func);

            if (!empty($params['consecutive'])) {
                $method->withConsecutive(...$params['consecutive']);
            }
        }

        $task = new RouteListTask();

        $task->mainAction();
    }

    public function testMainActionNoSubstitution()
    {
        $expected = [
            'write' => ['exactly' => 9, 'consecutive' => [
                ['+--------+------+----------+---------------------------+------------------------------------------------------+-------------------------------+'],
                ['| DOMAIN | NAME | METHOD   | PATTERN                   | ACTION                                               | MIDDLEWARE                    |'],
                ['+--------+------+----------+---------------------------+------------------------------------------------------+-------------------------------+'],
                ['|        |      | GET      | /get                      | App\Http\Controllers\StubController::index           |                               |'],
                ['|        |      | POST     | /post                     | Fake\Kernels\Http\Controllers\StubController::index  |                               |'],
                ['|        |      | GET      | /u/:int                   | Fake\Kernels\Http\Controllers\StubController::index  |                               |'],
                ['|        |      | GET|HEAD | /get-head                 | Fake\Kernels\Http\Controllers\StubController::index  | '.\Neutrino\Http\Middleware\Csrf::class.' |'],
                ['|        |      | GET      | /back/:controller/:action | Fake\Kernels\Http\Controllers\{controller}::{action} |                               |'],
                ['+--------+------+----------+---------------------------+------------------------------------------------------+-------------------------------+'],
            ]]
        ];

        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');
        $dispatcher->expects($this->any())->method('getOptions')->willReturn(['no-substitution' => true]);

        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        foreach ($expected as $func => $params) {
            $method = $mock->expects($this->exactly($params['exactly']))
                ->method($func);

            if (!empty($params['consecutive'])) {
                $method->withConsecutive(...$params['consecutive']);
            }
        }

        $task = new RouteListTask();

        $task->mainAction();
    }
}
