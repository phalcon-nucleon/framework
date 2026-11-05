<?php

declare(strict_types=1);

namespace Test\Events;

use Closure;
use Neutrino\Constants\Events;
use Neutrino\Events\Listener;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;
use Phalcon\Events\ManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ListenerTest extends TestCase
{
    public function testWrongAttach(): void
    {
        $listener = new StubWrongListener();
        $listener->setEventsManager(new Manager());

        $this->expectException(RuntimeException::class);

        $listener->attach();
    }

    public function testAttachWithoutEventsManager(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(StubGoodListener::class . ' has no events manager.');

        (new StubGoodListener())->attach();
    }

    /**
     * @return iterable<array{string, array<int|string, string>}>
     */
    public static function dataEvents(): iterable
    {
        yield ['space', [Events::APPLICATION]];
        yield ['space', [Events::DISPATCH]];
        yield ['listen', [Events\Http\Application::BEFORE_HANDLE => 'test']];
        yield ['listen', [Events\Dispatch::BEFORE_EXECUTE_ROUTE => 'test']];
    }

    /**
     * @param array<int|string, string> $event
     */
    #[DataProvider('dataEvents')]
    public function testAttachAndDetach(string $property, array $event): void
    {
        $listener = new StubGoodListener();
        $listener->configure($property, $event);

        $handler = $property === 'space' ? $listener : $this->isInstanceOf(Closure::class);
        $eventType = $property === 'space' ? array_values($event)[0] : array_keys($event)[0];

        $mock = $this->createMock(ManagerInterface::class);
        $mock->expects($this->once())->method('attach')->with($eventType, $handler);
        $mock->expects($this->once())->method('detach')->with($eventType, $handler);

        $listener->setEventsManager($mock);
        $this->assertSame($mock, $listener->getEventsManager());

        $listener->attach();
        $listener->detach();
    }

    public function testListenedMethodsAreCalled(): void
    {
        $em = new Manager();
        $listener = new StubGoodListener();
        $listener->configure('listen', ['custom:first' => 'test']);
        $listener->configure('space', ['other']);
        $listener->setEventsManager($em);
        $listener->attach();

        $em->fire('custom:first', $this, ['payload']);
        $em->fire('other:second', $this, 'data');

        $this->assertCount(2, $listener->calls);
        [$method, $event, $source, $data] = $listener->calls[0];
        $this->assertSame('test', $method);
        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('first', $event->getType());
        $this->assertSame($this, $source);
        $this->assertSame(['payload'], $data);
        $this->assertSame('second', $listener->calls[1][0]);

        $listener->detach();
        $em->fire('custom:first', $this);
        $em->fire('other:second', $this);

        $this->assertCount(2, $listener->calls);
    }
}

class StubGoodListener extends Listener
{
    /** @var list<array{string, mixed, mixed, mixed}> */
    public array $calls = [];

    /**
     * @param array<int|string, string> $events
     */
    public function configure(string $property, array $events): void
    {
        if ($property === 'space') {
            /** @var list<string> $events */
            $this->space = $events;
        } else {
            /** @var array<string, string> $events */
            $this->listen = $events;
        }
    }

    public function test(mixed $event, mixed $source, mixed $data = null): void
    {
        $this->calls[] = ['test', $event, $source, $data];
    }

    public function second(mixed $event, mixed $source, mixed $data = null): void
    {
        $this->calls[] = ['second', $event, $source, $data];
    }
}

class StubWrongListener extends Listener
{
    protected array $listen = ['test'];
}
