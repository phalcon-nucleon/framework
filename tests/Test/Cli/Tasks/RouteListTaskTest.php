<?php

namespace Test\Cli\Tasks;

use Fake\Kernels\Cli\StubKernelCli;
use Neutrino\Cli\Output\Decorate;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Debug\Reflexion;
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
        $routes = Reflexion::invoke($task, 'getHttpRoutesInfos');

        $this->assertInstanceOf(Router::class, $this->getDI()->getShared(Services::ROUTER));

        $this->assertEquals('Controller', $routes['controllerSuffix']);
        $this->assertEquals('Action', $routes['actionSuffix']);

        foreach ($expectedRoutes as $key => $expectedRoute) {
            $route = $routes['routes'][$key];

            $this->assertEquals($expectedRoute['pattern'], $route->getPattern());
            $this->assertEquals($expectedRoute['paths'], $route->getPaths());
        }
    }

    public function testMainAction()
    {
        $expected = [
            'write' => ['exactly' => 23, 'consecutive' => [
                ['+--------------+'],
                ['| MODULE    :  |'],
                ['| NAMESPACE :  |'],
                ['+--------------+'],
                ['+--------+------+--------+---------+-----------------------------+------------+'],
                ['| DOMAIN | NAME | METHOD | PATTERN | ACTION                      | MIDDLEWARE |'],
                ['+--------+------+--------+---------+-----------------------------+------------+'],
                ['|        |      | GET    | /get    | StubController::indexAction |            |'],
                ['+--------+------+--------+---------+-----------------------------+------------+'],
                [''],
                ['+-------------------------------------------+'],
                ['| MODULE    :                               |'],
                ['| NAMESPACE : Fake\Kernels\Http\Controllers |'],
                ['+-------------------------------------------+'],
                ['+--------+------+----------+-----------------------------+----------------------------------------+-------------------------------+'],
                ['| DOMAIN | NAME | METHOD   | PATTERN                     | ACTION                                 | MIDDLEWARE                    |'],
                ['+--------+------+----------+-----------------------------+----------------------------------------+-------------------------------+'],
                ['|        |      | POST     | /post                       | StubController::indexAction            |                               |'],
                ['|        |      | GET      | /u/'.Decorate::notice('{user}').'                   | StubController::indexAction            |                               |'],
                ['|        |      | GET|HEAD | /get-head                   | StubController::indexAction            | Neutrino\Http\Middleware\Csrf |'],
                ['|        |      | GET      | /back/'.Decorate::notice('{controller}').'/'.Decorate::notice('{action}').' | '.Decorate::notice('{controller}').'Controller::'.Decorate::notice('{action}').'Action |                               |'],
                ['+--------+------+----------+-----------------------------+----------------------------------------+-------------------------------+'],
            ]]
        ];

        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');

        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        foreach ($expected as $func => $params) {
            $method = $mock->expects($this->exactly($params['exactly']))->method($func);

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
            'write' => ['exactly' => 23, 'consecutive' => [
                ['+--------------+'],
                ['| MODULE    :  |'],
                ['| NAMESPACE :  |'],
                ['+--------------+'],
                ['+--------+------+--------+---------+-----------------------------+------------+'],
                ['| DOMAIN | NAME | METHOD | PATTERN | ACTION                      | MIDDLEWARE |'],
                ['+--------+------+--------+---------+-----------------------------+------------+'],
                ['|        |      | GET    | /get    | StubController::indexAction |            |'],
                ['+--------+------+--------+---------+-----------------------------+------------+'],
                [''],
                ['+-------------------------------------------+'],
                ['| MODULE    :                               |'],
                ['| NAMESPACE : Fake\Kernels\Http\Controllers |'],
                ['+-------------------------------------------+'],
                ['+--------+------+----------+---------------------------+----------------------------------------+-------------------------------+'],
                ['| DOMAIN | NAME | METHOD   | PATTERN                   | ACTION                                 | MIDDLEWARE                    |'],
                ['+--------+------+----------+---------------------------+----------------------------------------+-------------------------------+'],
                ['|        |      | POST     | /post                     | StubController::indexAction            |                               |'],
                ['|        |      | GET      | /u/:int                   | StubController::indexAction            |                               |'],
                ['|        |      | GET|HEAD | /get-head                 | StubController::indexAction            | Neutrino\Http\Middleware\Csrf |'],
                ['|        |      | GET      | /back/:controller/:action | '.Decorate::notice('{controller}').'Controller::'.Decorate::notice('{action}').'Action |                               |'],
                ['+--------+------+----------+---------------------------+----------------------------------------+-------------------------------+'],
            ]]
        ];

        $eventManager = $this->createMock(Manager::class);

        $dispatcher = $this->mockService(Services::DISPATCHER, Dispatcher::class, true);

        $dispatcher->expects($this->any())->method('getEventsManager')->willReturn($eventManager);
        $dispatcher->expects($this->any())->method('getActionSuffix')->willReturn('Action');
        $dispatcher->expects($this->any())->method('getOptions')->willReturn(['no-substitution' => true]);

        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        foreach ($expected as $func => $params) {
            $method = $mock->expects($this->exactly($params['exactly']))->method($func);

            if (!empty($params['consecutive'])) {
                $method->withConsecutive(...$params['consecutive']);
            }
        }

        $task = new RouteListTask();

        $task->mainAction();
    }
}
