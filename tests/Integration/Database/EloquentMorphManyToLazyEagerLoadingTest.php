<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphManyToLazyEagerLoadingTest;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentMorphManyToLazyEagerLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('org_deals');
        Schema::dropIfExists('fund_deals');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('org_deals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('org_id');
            $table->string('investment')->nullable();
            $table->timestamps();
        });

        Schema::create('fund_deals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('org_id');
            $table->string('call')->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('org_type')->nullable();
        });

        $user = User::create();

        /** @var Organization $org */
        $org = Organization::query()->create();
        $fundDeal = tap((new FundDeal(['org_id' => $org->getKey()]))->user()->associate($user))->save();
        $org->deals()->associate($fundDeal)->save();

        $org = Organization::query()->create();
        $orgDeal = OrgDeal::query()->create(['org_id' => $org->getKey()]);
        $org->deals()->associate($orgDeal)->save();
    }

    public function testLazyEagerLoading()
    {
        $organizations = Organization::all();

        DB::enableQueryLog();

        $organizations->load('deals');

        $this->assertCount(3, DB::getQueryLog());
        $this->assertTrue($organizations[0]->relationLoaded('deals'));
        $this->assertTrue($organizations[0]->deals->first()->relationLoaded('user'));
        $this->assertTrue($organizations[1]->relationLoaded('deals'));
    }
}

class User extends Model
{
    public $timestamps = false;
}

class Organization extends Model
{
    public $timestamps = false;

    public function deals()
    {
        return $this->morphManyTo('deals', 'org_type', 'id', 'org_id');
    }
}

class FundDeal extends Model
{
    protected $guarded = [];
    protected $with = ['user'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class OrgDeal extends Model
{
    protected $guarded = [];
}
