<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery as m;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use Throwable;

#[WithMigration('queue')]
class QueueConnectionTest extends TestCase
{
    use DatabaseMigrations, InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        QueueConnectionTestJob::$ran = false;

        $this->tearDownRedis();
        m::close();
    }

    public function testJobWontGetDispatchedInsideATransaction()
    {
        $this->app['config']->set('queue.default', 'sqs');
        $this->app['config']->set('queue.connections.sqs.after_commit', true);
        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);

            return $transactionManager;
        });

        Bus::dispatch(new QueueConnectionTestJob);
    }

    public function testJobWillGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app['config']->set('queue.default', 'sqs');
        $this->app['config']->set('queue.connections.sqs.after_commit', true);
        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldNotReceive('addCallback')->andReturn(null);

            return $transactionManager;
        });

        try {
            Bus::dispatch((new QueueConnectionTestJob)->beforeCommit());
        } catch (Throwable) {
            // This job was dispatched
        }
    }

    public function testJobWontGetDispatchedInsideATransactionWhenExplicitlyIndicated()
    {
        $this->app['config']->set('queue.default', 'sqs');
        $this->app['config']->set('queue.connections.sqs.after_commit', false);

        $this->app->singleton('db.transactions', function () {
            $transactionManager = m::mock(DatabaseTransactionsManager::class);
            $transactionManager->shouldReceive('addCallback')->once()->andReturn(null);

            return $transactionManager;
        });

        try {
            Bus::dispatch((new QueueConnectionTestJob)->afterCommit());
        } catch (SqsException) {
            // This job was dispatched
        }
    }

    /**
     * @dataProvider connectionQueueDataProvider
     */
    public function testUserSpecifiedConnectionAndQueueAreStoredInPayload($job, $connection, $queue, $setUp = null)
    {
        Config::set('queue.default', 'database');
        ($setUp ?? fn () => null)();
        $payload = null;
        Event::listen(function (JobQueued $event) use (&$payload) {
            $payload = $event->payload();
        });

        Bus::dispatch($job);

        $this->assertNotNull($payload);
        $this->assertSame($connection, $payload['connection']);
        $this->assertSame($queue, $payload['queue']);
    }

    public static function connectionQueueDataProvider()
    {
        return [
            'null null' => [new ConnectionAndQueueJob(connection: null, queue: null), 'database', 'default'],
            'database null' => [new ConnectionAndQueueJob(connection: 'database', queue: null), 'database', 'default'],
            'database named-queue' => [new ConnectionAndQueueJob(connection: 'database', queue: 'named-queue'), 'database', 'named-queue'],
            'database configured-default' => [new ConnectionAndQueueJob(connection: 'database', queue: null), 'database', 'configured-default', function () {
                Config::set('queue.connections.database.queue', 'configured-default');
            }],
            'redis null' => [new ConnectionAndQueueJob(connection: 'redis', queue: null), 'redis', 'default'],
            'redis named-queue' => [new ConnectionAndQueueJob(connection: 'redis', queue: 'named-queue'), 'redis', 'named-queue'],
            'redis configured-default' => [new ConnectionAndQueueJob(connection: 'redis', queue: null), 'redis', 'configured-default', function () {
                Config::set('queue.connections.redis.queue', 'configured-default');
            }],
            'redis_xl configured-default' => [new ConnectionAndQueueJob(connection: 'redis_xl', queue: null), 'redis_xl', 'configured-default', function () {
                Config::set('queue.connections.redis_xl', Config::get('queue.connections.redis'));
                Config::set('queue.connections.redis_xl.queue', 'configured-default');
            }],
        ];
    }
}

class QueueConnectionTestJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class ConnectionAndQueueJob implements ShouldQueue
{
    public function __construct(public $connection = null, public $queue = null)
    {
        //
    }

    public function handle()
    {
        //
    }
}
