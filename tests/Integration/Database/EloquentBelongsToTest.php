<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentBelongsToTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('parent_slug')->nullable();
        });

        $user = User1::create(['slug' => str_random()]);
        User1::create(['parent_id' => $user->id, 'parent_slug' => $user->slug]);
    }

    public function test_has_self()
    {
        $users = User1::has('parent')->get();

        $this->assertEquals(1, $users->count());
    }

    public function test_has_self_custom_owner_key()
    {
        $users = User1::has('parentBySlug')->get();

        $this->assertEquals(1, $users->count());
    }
}

class User1 extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function parentBySlug()
    {
        return $this->belongsTo(self::class, 'parent_slug', 'slug');
    }
}
