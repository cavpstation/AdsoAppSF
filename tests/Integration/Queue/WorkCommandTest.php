<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration('queue')]
class WorkCommandTest extends QueueTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            FirstJob::$ran = false;
            SecondJob::$ran = false;
            ThirdJob::$ran = false;
        });

        parent::setUp();
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        if ($this->driver === 'sync') {
            $this->markTestSkipped('Unable to test `queue:work` on `sync` driver');
        }
    }

    public function testRunningOneJob()
    {
        Queue::push(new FirstJob);
        Queue::push(new SecondJob);

        $this->runQueueWorkCommand();

        $this->assertSame(1, Queue::size());
        $this->assertTrue(FirstJob::$ran);
        $this->assertFalse(SecondJob::$ran);
    }

    public function testRunTimestampOutputWithDefaultAppTimezone()
    {
        // queue.output_timezone not set at all
        $this->travelTo(Carbon::create(2023, 1, 18, 10, 10, 11));
        Queue::connection('database')->push(new FirstJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--memory' => 1024,
        ])->expectsOutputToContain('2023-01-18 10:10:11')
            ->assertExitCode(0);
    }

    public function testRunTimestampOutputWithDifferentLogTimezone()
    {
        $this->app['config']->set('queue.output_timezone', 'Europe/Helsinki');

        $this->travelTo(Carbon::create(2023, 1, 18, 10, 10, 11));
        Queue::connection('database')->push(new FirstJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--memory' => 1024,
        ])->expectsOutputToContain('2023-01-18 12:10:11')
            ->assertExitCode(0);
    }

    public function testRunTimestampOutputWithSameAppDefaultAndQueueLogDefault()
    {
        $this->app['config']->set('queue.output_timezone', 'UTC');

        $this->travelTo(Carbon::create(2023, 1, 18, 10, 10, 11));
        Queue::connection('database')->push(new FirstJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--memory' => 1024,
        ])->expectsOutputToContain('2023-01-18 10:10:11')
            ->assertExitCode(0);
    }

    public function testDaemon()
    {
        Queue::connection('database')->push(new FirstJob);
        Queue::connection('database')->push(new SecondJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--daemon' => true,
            '--stop-when-empty' => true,
            '--memory' => 1024,
        ])->assertExitCode(0);

        $this->assertSame(0, Queue::connection('database')->size());
        $this->assertTrue(FirstJob::$ran);
        $this->assertTrue(SecondJob::$ran);
    }

    public function testMemoryExceeded()
    {
        Queue::connection('database')->push(new FirstJob);
        Queue::connection('database')->push(new SecondJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--daemon' => true,
            '--stop-when-empty' => true,
            '--memory' => 0.1,
        ])->assertExitCode(12);

        // Memory limit isn't checked until after the first job is attempted.
        $this->assertSame(1, Queue::connection('database')->size());
        $this->assertTrue(FirstJob::$ran);
        $this->assertFalse(SecondJob::$ran);
    }

    public function testMaxJobsExceeded()
    {
        Queue::connection('database')->push(new FirstJob);
        Queue::connection('database')->push(new SecondJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--daemon' => true,
            '--stop-when-empty' => true,
            '--max-jobs' => 1,
        ]);

        // Memory limit isn't checked until after the first job is attempted.
        $this->assertSame(1, Queue::connection('database')->size());
        $this->assertTrue(FirstJob::$ran);
        $this->assertFalse(SecondJob::$ran);
    }

    public function testMaxTimeExceeded()
    {
        Queue::connection('database')->push(new ThirdJob);
        Queue::connection('database')->push(new FirstJob);
        Queue::connection('database')->push(new SecondJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--daemon' => true,
            '--stop-when-empty' => true,
            '--max-time' => 1,
        ]);

        // Memory limit isn't checked until after the first job is attempted.
        $this->assertSame(2, Queue::connection('database')->size());
        $this->assertTrue(ThirdJob::$ran);
        $this->assertFalse(FirstJob::$ran);
        $this->assertFalse(SecondJob::$ran);
    }
}

class FirstJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class SecondJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class ThirdJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        sleep(1);

        static::$ran = true;
    }
}
