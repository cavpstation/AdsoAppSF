<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Foundation\Bus\Exceptions\JobDispatchedException;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class UniqueJobDispatchingTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
    }

    public function testSubsequentUniqueJobDispatchAreIgnored()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        Queue::fake();

        Fixtures\UniqueJob::dispatch();
        Fixtures\UniqueJob::dispatch();

        Queue::assertPushed(Fixtures\UniqueJob::class);
    }
}
