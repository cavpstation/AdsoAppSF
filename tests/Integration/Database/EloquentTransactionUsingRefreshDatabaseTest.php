<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Factories\UserFactory;

class EloquentTransactionUsingRefreshDatabaseTest extends DatabaseTestCase
{
    use RefreshDatabase, WithLaravelMigrations;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(fn () => User::unguard());
        $this->beforeApplicationDestroyed(fn () => User::reguard());

        parent::setUp();
    }

    public function testObserverIsCalledOnTestsWithAfterCommit()
    {
        User::observe($observer = EloquentTransactionUsingRefreshDatabaseUserObserver::resetting());

        $user1 = User::create(UserFactory::new()->raw());

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverCalledWithAfterCommitWhenInsideTransaction()
    {
        User::observe($observer = EloquentTransactionUsingRefreshDatabaseUserObserver::resetting());

        $user1 = DB::transaction(fn () => User::create(UserFactory::new()->raw()));

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverIsCalledOnTestsWithAfterCommitWhenUsingSavepoint()
    {
        User::observe($observer = EloquentTransactionUsingRefreshDatabaseUserObserver::resetting());

        $user1 = User::createOrFirst(UserFactory::new()->raw());

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverIsCalledOnTestsWithAfterCommitWhenUsingSavepointAndInsideTransaction()
    {
        User::observe($observer = EloquentTransactionUsingRefreshDatabaseUserObserver::resetting());

        $user1 = DB::transaction(fn () => User::createOrFirst(UserFactory::new()->raw()));

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverIsCalledEvenWhenDeeplyNestingTransactions()
    {
        User::observe($observer = EloquentTransactionUsingRefreshDatabaseUserObserver::resetting());

        $user1 = DB::transaction(function () {
            return tap(DB::transaction(function () {
                return DB::transaction(function () {
                    return tap(User::createOrFirst(UserFactory::new()->raw()), function () {
                        //$this->assertEquals(0, $observer::$calledTimes, 'Should not have been called');
                    });
                });
            }), function () {
                // $this->assertEquals(0, $observer::$calledTimes, 'Should not have been called');
            });
        });

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }
}

class EloquentTransactionUsingRefreshDatabaseUserObserver
{
    public static $calledTimes = 0;

    public $afterCommit = true;

    public static function resetting()
    {
        static::$calledTimes = 0;

        return new static();
    }

    public function created($user)
    {
        static::$calledTimes++;
    }
}
