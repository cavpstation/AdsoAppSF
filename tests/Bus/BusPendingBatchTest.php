<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class BusPendingBatchTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testPendingBatchMayBeConfiguredAndDispatched()
    {
        $container = new Container;

        $eventDispatcher = m::mock(Dispatcher::class);
        $eventDispatcher->shouldReceive('dispatch')->once();

        $container->instance(Dispatcher::class, $eventDispatcher);

        $job = new class
        {
            use Batchable;
        };

        $pendingBatch = new PendingBatch($container, new Collection([$job]));

        $pendingBatch = $pendingBatch->then(function () {
            //
        })->catch(function () {
            //
        })->allowFailures()->onConnection('test-connection')->onQueue('test-queue');

        $this->assertSame('test-connection', $pendingBatch->connection());
        $this->assertSame('test-queue', $pendingBatch->queue());
        $this->assertCount(1, $pendingBatch->thenCallbacks());
        $this->assertCount(1, $pendingBatch->catchCallbacks());

        $repository = m::mock(BatchRepository::class);
        $repository->shouldReceive('store')->once()->with($pendingBatch)->andReturn($batch = m::mock(stdClass::class));
        $batch->shouldReceive('add')->once()->with(m::type(Collection::class))->andReturn($batch = m::mock(Batch::class));

        $container->instance(BatchRepository::class, $repository);

        $pendingBatch->dispatch();
    }

    public function testBatchIsDeletedFromStorageIfExceptionThrownDuringBatching()
    {
        $this->expectException(RuntimeException::class);

        $container = new Container;

        $job = new class
        {
        };

        $pendingBatch = new PendingBatch($container, new Collection([$job]));

        $repository = m::mock(BatchRepository::class);

        $repository->shouldReceive('store')->once()->with($pendingBatch)->andReturn($batch = m::mock(stdClass::class));

        $batch->id = 'test-id';

        $batch->shouldReceive('add')->once()->andReturnUsing(function () {
            throw new RuntimeException('Failed to add jobs...');
        });

        $repository->shouldReceive('delete')->once()->with('test-id');

        $container->instance(BatchRepository::class, $repository);

        $pendingBatch->dispatch();
    }
}
