<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @group one-of-many
 */
class DatabaseEloquentHasOneOfManyTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
        });

        $this->schema()->create('logins', function ($table) {
            $table->increments('id');
            $table->foreignId('user_id');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('logins');
    }

    public function testItEagerLoadsCorrectModels()
    {
        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $user = HasOneOfManyTestUser::with('latest_login')->first();

        $this->assertTrue($user->relationLoaded('latest_login'));
        $this->assertSame($latestLogin->id, $user->latest_login->id);
    }

    
    public function testHasNested()
    {
        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $found = HasOneOfManyTestUser::whereHas('latest_login', function ($query) use ($latestLogin) {
            $query->where('id', $latestLogin->id);
        })->exists();
        $this->assertTrue($found);

        $found = HasOneOfManyTestUser::whereHas('latest_login', function ($query) use ($previousLogin) {
            $query->where('id', $previousLogin->id);
        })->exists();
        $this->assertFalse($found);
    }

    public function testHasCount()
    {
        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $user->logins()->create();

        $user = HasOneOfManyTestUser::withCount('latest_login')->first();
        $this->assertEquals(1, $user->latest_login_count);
    }

    public function testExists()
    {
        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $this->assertFalse($user->latest_login()->whereKey($previousLogin->getKey())->exists());
        $this->assertTrue($user->latest_login()->whereKey($latestLogin->getKey())->exists());
    }

    public function testIsMethod()
    {
        $user = HasOneOfManyTestUser::create();
        $login1 = $user->latest_login()->create();
        $login2 = $user->latest_login()->create();

        $this->assertFalse($user->latest_login()->is($login1));
        $this->assertTrue($user->latest_login()->is($login2));
    }

    public function testIsNotMethod()
    {
        $user = HasOneOfManyTestUser::create();
        $login1 = $user->latest_login()->create();
        $login2 = $user->latest_login()->create();

        $this->assertTrue($user->latest_login()->isNot($login1));
        $this->assertFalse($user->latest_login()->isNot($login2));
    }

    /**
     * @group fail
     */
    public function testGet()
    {
        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $latestLogins = $user->latest_login()->get();
        $this->assertCount(1, $latestLogins);
        $this->assertSame($latestLogin->id, $latestLogins->first()->id);

        $latestLogins = $user->latest_login()->whereKey($previousLogin->getKey())->get();
        $this->assertCount(0, $latestLogins);
    }

    public function testCount()
    {
        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $user->logins()->create();

        $this->assertSame(1, $user->latest_login()->count());
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class HasOneOfManyTestUser extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function logins()
    {
        return $this->hasMany(HasOneOfManyTestLogin::class, 'user_id');
    }

    public function latest_login()
    {
        return $this->hasOne(HasOneOfManyTestLogin::class, 'user_id')->ofMany()->orderByDesc('id');
    }
}

class HasOneOfManyTestLogin extends Eloquent
{
    protected $table = 'logins';
    protected $guarded = [];
    public $timestamps = false;
}
