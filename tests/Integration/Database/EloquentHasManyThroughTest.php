<?php

namespace Illuminate\Tests\Integration\Database\EloquentHasManyThroughTest;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentHasManyThroughTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->integer('team_id')->nullable();
            $table->string('name');
        });

        Schema::create('teams', function ($table) {
            $table->increments('id');
            $table->integer('owner_id');
        });
    }

    /**
     * @test
     */
    public function basic_create_and_retrieve()
    {
        $user = User::create(['name' => str_random()]);

        $team1 = Team::create(['owner_id' => $user->id]);
        $team2 = Team::create(['owner_id' => $user->id]);

        $mate1 = User::create(['name' => str_random(), 'team_id' => $team1->id]);
        $mate2 = User::create(['name' => str_random(), 'team_id' => $team2->id]);

        $notMember = User::create(['name' => str_random()]);

        $this->assertEquals([$mate1->id, $mate2->id], $user->teamMates->pluck('id')->toArray());
        $this->assertEquals([$user->id], User::has('teamMates')->pluck('id')->toArray());
    }

    /**
     * @test
     */
    public function retrieve_results_in_chunks_without_attribute_shadowing()
    {
        $user = $this->stubUserTeamMates();

        $ids_by_get = $user->teamMates()->forPage(1, 10)->get()->pluck('id');

        $ids_by_chunk = collect();
        $user->teamMates()->chunk(10, function ($posts) use (&$ids_by_chunk) {
            $ids_by_chunk = $ids_by_chunk->merge($posts->pluck('id'));
        });

        $this->assertEquals($ids_by_get->toArray(), $ids_by_chunk->toArray());
    }

    /**
     * @test
     */
    public function pluck_column_without_attribute_shadowing()
    {
        $user = $this->stubUserTeamMates();

        $mates_by_get = $user->teamMates()->get();

        $mates_by_cursor = collect($user->teamMates()->cursor());

        $this->assertEquals($mates_by_get->toArray(), $mates_by_cursor->toArray());
    }

    protected function stubUserTeamMates()
    {
        $user = User::create(['name' => str_random()]);

        $team = Team::create(['owner_id' => $user->id]);

        $mate1 = User::create(['name' => str_random(), 'team_id' => $team->id]);
        $mate2 = User::create(['name' => str_random(), 'team_id' => $team->id]);

        return $user;
    }
}

class User extends Model
{
    public $table = 'users';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function teamMates()
    {
        return $this->hasManyThrough(self::class, Team::class, 'owner_id', 'team_id');
    }
}

class Team extends Model
{
    public $table = 'teams';
    public $timestamps = false;
    protected $guarded = ['id'];
}
