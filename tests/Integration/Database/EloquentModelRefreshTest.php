<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelRefreshTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentModelRefreshTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * @test
     */
    public function it_refreshes_model_excluded_by_global_scope()
    {
        $post = Post::create(['title' => 'mohamed']);

        $post->refresh();
    }

    /**
     * @test
     */
    public function it_refreshes_a_soft_deleted_model()
    {
        $post = Post::create(['title' => 'said']);

        Post::find($post->id)->delete();

        $this->assertFalse($post->trashed());

        $post->refresh();

        $this->assertTrue($post->trashed());
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = ['id'];

    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('age', function ($query) {
            $query->where('title', '!=', 'mohamed');
        });
    }
}
