<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Testing\Fakes\EventFake;
use Mockery as m;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SupportTestingEventFakeTest extends TestCase
{
    protected $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new EventFake(m::mock(Dispatcher::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testAssertDispatched()
    {
        try {
            $this->fake->assertDispatched(EventStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\EventStub] event was not dispatched.', $e->getMessage());
        }

        $this->fake->dispatch(EventStub::class);

        $this->fake->assertDispatched(EventStub::class);
    }

    public function testAssertDispatchedWithClosure()
    {
        $this->fake->dispatch(new EventStub);

        $this->fake->assertDispatched(function (EventStub $event) {
            return true;
        });
    }

    public function testAssertListening()
    {
        $listener = ListenerStub::class;

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('getListeners')->andReturn([function ($event, $payload) use ($listener) {
            return $listener(...array_values($payload));
        }]);

        $fake = new EventFake($dispatcher);

        $fake->assertListening(EventStub::class, ListenerStub::class);
    }

    public function testAssertNotListening()
    {
        // Case 1: Listener is NOT attached, should pass
        $listener = ListenerStub::class;

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('getListeners')->andReturn([]);

        $fake = new EventFake($dispatcher);

        $fake->assertNotListening(EventStub::class, ListenerStub::class);

        // Case 2: Listener is attached, should fail
        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('getListeners')->andReturn([function ($event, $payload) use ($listener) {
            return $listener(...array_values($payload));
        }]);

        $fake = new EventFake($dispatcher);

        $this->expectException(ExpectationFailedException::class);
        $fake->assertNotListening(EventStub::class, ListenerStub::class);
    }

    public function testAssertNotListeningWithStringCallback()
    {
        // Case 3: Listener attached as a string with method, should fail
        $listener = ListenerStub::class.'@handle';

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('getListeners')->andReturn([function ($event, $payload) use ($listener) {
            return $listener(...array_values($payload));
        }]);

        $fake = new EventFake($dispatcher);

        $this->expectException(ExpectationFailedException::class);
        $fake->assertNotListening(EventStub::class, $listener);
    }

    public function testAssertNotListeningWithDifferentListeners()
    {
        // Case 4: Different listener should pass
        $actualListener = AnotherListenerStub::class;

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('getListeners')->andReturn([function ($event, $payload) use ($actualListener) {
            return $actualListener(...array_values($payload));
        }]);

        $fake = new EventFake($dispatcher);

        $fake->assertNotListening(EventStub::class, ListenerStub::class);
    }

    public function testAssertNotListeningWithClosureListener()
    {
        // Case 5: Closure listener, should pass
        $listener = function () {
            // Closure listener logic
        };

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('getListeners')->andReturn([$listener]);

        $fake = new EventFake($dispatcher);

        $fake->assertNotListening(EventStub::class, ListenerStub::class);
    }

    public function testAssertNotListeningWithClosureListenerAsClosureClass()
    {
        // Case 6: Closure listener, expecting Closure::class, should pass
        $listener = function () {
            // Closure logic
        };

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('getListeners')->andReturn([$listener]);

        $fake = new EventFake($dispatcher);

        $fake->assertNotListening(EventStub::class, Closure::class);
    }

    public function testAssertNotListeningWithMatchingParsedCallback()
    {
        // Case 7: Listener with parsed callback, should fail
        $listener = ListenerStub::class.'@handle';

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('getListeners')->andReturn([function ($event, $payload) use ($listener) {
            return $listener(...array_values($payload));
        }]);

        $fake = new EventFake($dispatcher);

        $this->expectException(ExpectationFailedException::class);
        $fake->assertNotListening(EventStub::class, ListenerStub::class.'@handle');
    }

    public function testAssertDispatchedWithCallbackInt()
    {
        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertDispatched(EventStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\EventStub] event was dispatched 2 times instead of 1 times.', $e->getMessage());
        }

        $this->fake->assertDispatched(EventStub::class, 2);
    }

    public function testAssertDispatchedTimes()
    {
        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertDispatchedTimes(EventStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\EventStub] event was dispatched 2 times instead of 1 times.', $e->getMessage());
        }

        $this->fake->assertDispatchedTimes(EventStub::class, 2);
    }

    public function testAssertNotDispatched()
    {
        $this->fake->assertNotDispatched(EventStub::class);

        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertNotDispatched(EventStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The unexpected [Illuminate\Tests\Support\EventStub] event was dispatched.', $e->getMessage());
        }
    }

    public function testAssertNotDispatchedWithClosure()
    {
        $this->fake->dispatch(new EventStub);

        try {
            $this->fake->assertNotDispatched(function (EventStub $event) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The unexpected [Illuminate\Tests\Support\EventStub] event was dispatched.', $e->getMessage());
        }
    }

    public function testAssertDispatchedWithIgnore()
    {
        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once();

        $fake = new EventFake($dispatcher, [
            'Foo',
            function ($event, $payload) {
                return $event === 'Bar' && $payload['id'] === 1;
            },
        ]);

        $fake->dispatch('Foo');
        $fake->dispatch('Bar', ['id' => 1]);
        $fake->dispatch('Baz');

        $fake->assertDispatched('Foo');
        $fake->assertDispatched('Bar');
        $fake->assertNotDispatched('Baz');
    }

    public function testAssertNothingDispatched()
    {
        $this->fake->assertNothingDispatched();

        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertNothingDispatched();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString("2 unexpected events were dispatched:\n\n- Illuminate\Tests\Support\EventStub dispatched 2 times", $e->getMessage());
        }
    }
}

class EventStub
{
    //
}

class ListenerStub
{
    //
}

class AnotherListenerStub
{
    //
}
