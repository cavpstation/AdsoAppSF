<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @group integration
 */
class EloquentWhereHasTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->boolean('public');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $user = User6::create();
        $post = tap((new Post(['public' => true]))->user()->associate($user))->save();
        (new Comment)->commentable()->associate($post)->save();

        $user = User6::create();
        $post = tap((new Post(['public' => false]))->user()->associate($user))->save();
        (new Comment)->commentable()->associate($post)->save();
    }

    public function test_with_count()
    {
        $users = User6::whereHas('posts', function ($query) {
            $query->where('public', true);
        })->get();

        $this->assertEquals([1], $users->pluck('id')->all());
    }
}

class Comment extends Model
{
    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function user()
    {
        return $this->belongsTo(User6::class);
    }
}

class User6 extends Model
{
    public $timestamps = false;

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
