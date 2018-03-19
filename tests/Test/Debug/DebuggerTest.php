<?php

namespace Test\Debug;

use Neutrino\Constants\Services;
use Neutrino\Debug\DebugEventsManagerWrapper;
use Neutrino\Debug\Debugger;
use Neutrino\Debug\Reflexion;
use Phalcon\Db\Adapter\Pdo;
use Phalcon\Db\Profiler;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\Manager;
use Phalcon\Events\ManagerInterface;
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Test\TestCase\TestCase;

class DebuggerTest extends TestCase
{
    public function tearDown()
    {
        Reflexion::set(Debugger::class, 'viewProfiles', null);
        Reflexion::set(Debugger::class, 'profilers', null);
        Reflexion::set(Debugger::class, 'instance', null);
        Reflexion::set(Debugger::class, 'view', null);

        parent::tearDown();
    }

    public function testRegisterGlobalEventManager()
    {
        $debugger = Reflexion::getReflectionClass(Debugger::class)->newInstanceWithoutConstructor();

        Reflexion::set($this->getDI(), '_eventsManager', $dim = new Manager());
        $this->getDI()->set(Services::EVENTS_MANAGER, $em = new Manager());
        Reflexion::set($this->app, '_eventsManager', $appm = new Manager());

        Reflexion::invoke($debugger, 'registerGlobalEventManager');

        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $this->getDI()->getInternalEventsManager());
        $this->assertEquals($dim, Reflexion::get($this->getDI()->getInternalEventsManager(), 'manager'));
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $this->getDI()->get(Services::EVENTS_MANAGER));
        $this->assertEquals($em, Reflexion::get($this->getDI()->get(Services::EVENTS_MANAGER), 'manager'));
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $this->app->getEventsManager());
        $this->assertEquals($appm, Reflexion::get($this->app->getEventsManager(), 'manager'));

        $this->getDI()->remove(Services::EVENTS_MANAGER);
        Reflexion::set($this->getDI(), '_eventsManager', null);
        Reflexion::set($this->app, '_eventsManager', null);

        Reflexion::invoke($debugger, 'registerGlobalEventManager');

        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $this->getDI()->getInternalEventsManager());
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $this->getDI()->get(Services::EVENTS_MANAGER));
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $this->app->getEventsManager());
    }


    public function testGetGlobalEventsManager()
    {
        try {
            Debugger::getGlobalEventsManager();
        } catch (\Exception $e) {
        }
        $this->assertTrue(isset($e));
        $this->assertEquals('Exception', get_class($e));
        $this->assertEquals('Debugger wasn\'t registered', $e->getMessage());

        $debugger = Reflexion::getReflectionClass(Debugger::class)->newInstanceWithoutConstructor();
        Reflexion::set($debugger, 'instance', $debugger);
        Reflexion::invoke($debugger, 'registerGlobalEventManager');

        $this->assertInstanceOf(DebugEventsManagerWrapper::class, Debugger::getGlobalEventsManager());
    }

    public function testRegisterProfiler()
    {
        $profiler = Reflexion::invoke(Debugger::class, 'registerProfiler', 'db');

        $this->assertInstanceOf(Profiler::class, $profiler);

        $profiler2 = Reflexion::invoke(Debugger::class, 'registerProfiler', 'db');

        $this->assertEquals($profiler, $profiler2);
    }

    public function testAttachEventsManager()
    {
        $debugger = Reflexion::getReflectionClass(Debugger::class)->newInstanceWithoutConstructor();

        Reflexion::invoke($debugger, 'registerGlobalEventManager');
        Reflexion::invoke($debugger, 'tryAttachEventsManager', '');

        $dispatcher = $this->getDI()->get(Services::DISPATCHER);
        Reflexion::set($dispatcher, '_eventsManager', null);
        Reflexion::invoke($debugger, 'tryAttachEventsManager', $dispatcher);
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $dispatcher->getEventsManager());

        Reflexion::set($dispatcher, '_eventsManager', $em = new Manager());
        Reflexion::invoke($debugger, 'tryAttachEventsManager', $dispatcher);
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $dispatcher->getEventsManager());
        $this->assertEquals($em, Reflexion::get($dispatcher->getEventsManager(), 'manager'));

        Reflexion::set($dispatcher, '_eventsManager', $em = new DebugEventsManagerWrapper(new Manager()));
        Reflexion::invoke($debugger, 'tryAttachEventsManager', $dispatcher);
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $dispatcher->getEventsManager());
        $this->assertEquals($em, $dispatcher->getEventsManager());
    }

    public function testListenLoader()
    {
        $debugger = Reflexion::getReflectionClass(Debugger::class)->newInstanceWithoutConstructor();
        Reflexion::invoke($debugger, 'registerGlobalEventManager');
        Reflexion::invoke($debugger, 'listenLoader');

        global $loader;

        $loader = new Loader();
        Reflexion::invoke($debugger, 'listenLoader');
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $loader->getEventsManager());

        unset($loader);
    }

    public function testListenServices()
    {
        $debugger = Reflexion::getReflectionClass(Debugger::class)->newInstanceWithoutConstructor();
        Reflexion::invoke($debugger, 'registerGlobalEventManager');
        Reflexion::invoke($debugger, 'listenServices');
        $gem = Reflexion::get($debugger, 'em');

        $em = Reflexion::get($this->getDI()->getInternalEventsManager(), 'manager');

        $events = Reflexion::get($em, '_events');

        $this->assertArrayHasKey('di:afterServiceResolve', $events);

        $this->getDI()->set('my-service', $service = new StubService());
        $this->getDI()->get('my-service');
        $em = $service->getEventsManager();
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $em);

        $this->getDI()->get('my-service');
        $this->assertEquals($em, $service->getEventsManager());

        $this->getDI()->set('my-db', $service = Reflexion::getReflectionClass(Pdo\Mysql::class)->newInstanceWithoutConstructor());
        $this->getDI()->get('my-db');
        $em = $service->getEventsManager();
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $em);
        $this->assertInstanceOf(Profiler::class, Reflexion::get(Debugger::class, 'profilers')['db']['profiler']);
        $this->assertNotEmpty($gem->getListeners('db'));

        $this->getDI()->set('my-view', $service = Reflexion::getReflectionClass(View::class)->newInstanceWithoutConstructor());
        $this->getDI()->get('my-view');
        $em = $service->getEventsManager();
        $this->assertInstanceOf(DebugEventsManagerWrapper::class, $em);
        $this->assertNotEmpty($gem->getListeners('view'));
    }
}

class StubService implements EventsAwareInterface
{
    protected $em;

    /**
     * Sets the events manager
     *
     * @param ManagerInterface $eventsManager
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        $this->em = $eventsManager;
    }

    /**
     * Returns the internal event manager
     *
     * @return ManagerInterface
     */
    public function getEventsManager()
    {
        return $this->em;
    }
}
