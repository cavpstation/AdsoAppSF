<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseSoftDeletingTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testDeleteSetsSoftDeletedColumn()
    {
        $model = m::mock(DatabaseSoftDeletingTraitStub::class);
        $model->makePartial();
        $model->shouldReceive('newModelQuery')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('id', '=', 1)->andReturn($query);
        $query->shouldReceive('update')->once()->with([
            'deleted_at' => 'date-time',
            'updated_at' => 'date-time',
        ]);
        $model->shouldReceive('syncOriginalAttributes')->once()->with([
            'deleted_at',
            'updated_at',
        ]);
        $model->delete();

        $this->assertInstanceOf(Carbon::class, $model->deleted_at);
    }

    public function testRestore()
    {
        $model = m::mock(DatabaseSoftDeletingTraitStub::class);
        $model->makePartial();
        $model->shouldReceive('fireModelEvent')->with('restoring')->andReturn(true);
        $model->shouldReceive('save')->once();
        $model->shouldReceive('fireModelEvent')->with('restored', false)->andReturn(true);

        $model->restore();

        $this->assertNull($model->deleted_at);
    }

    public function testRestoreCancel()
    {
        $model = m::mock(DatabaseSoftDeletingTraitStub::class);
        $model->makePartial();
        $model->shouldReceive('fireModelEvent')->with('restoring')->andReturn(false);
        $model->shouldReceive('save')->never();

        $this->assertFalse($model->restore());
    }

    public function testTrashAt()
    {
        Carbon::setTestNow($now = now());

        $now = $now->clone()->addSecond();

        $model = m::mock(DatabaseSoftDeletingTraitStub::class);
        $model->makePartial();
        $model->shouldReceive('newModelQuery')->once()->andReturn($query = m::mock(stdClass::class));
        $model->shouldReceive('fireModelEvent')->once()->with('willTrash', false)->andReturn(true);
        $query->shouldReceive('where')->once()->with('id', '=', 1)->andReturn($query);
        $query->shouldReceive('update')->once()->with([
            'deleted_at' => 'date-time',
            'updated_at' => 'date-time',
        ]);
        $model->shouldReceive('syncOriginalAttributes')->once()->with([
            'deleted_at',
            'updated_at',
        ]);

        $model->trashAt($now->addSecond());

        $this->assertInstanceOf(Carbon::class, $model->deleted_at);
        $this->assertEquals($now, $model->deleted_at);
    }

    public function testTrashAtThrowsExceptionIfDatetimePresent()
    {
        $this->expectExceptionMessage(InvalidArgumentException::class);
        $this->expectExceptionMessage('The datetime must be set in the future.');

        Carbon::setTestNow($now = now());

        $model = m::mock(DatabaseSoftDeletingTraitStub::class);
        $model->makePartial();
        $model->shouldNotReceive('newModelQuery');

        $model->trashAt($now->addSecond());
    }

    public function testTrashAtThrowsExceptionIfDatetimePast()
    {
        $this->expectExceptionMessage(InvalidArgumentException::class);
        $this->expectExceptionMessage('The datetime must be set in the future.');

        Carbon::setTestNow($now = now());

        $now = $now->clone()->subSecond();

        $model = m::mock(DatabaseSoftDeletingTraitStub::class);
        $model->makePartial();
        $model->shouldNotReceive('newModelQuery');

        $model->trashAt($now->addSecond());
    }
}

class DatabaseSoftDeletingTraitStub
{
    use SoftDeletes;
    public $deleted_at;
    public $updated_at;
    public $timestamps = true;

    public function newQuery()
    {
        //
    }

    public function getKey()
    {
        return 1;
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function save()
    {
        //
    }

    public function delete()
    {
        return $this->performDeleteOnModel();
    }

    public function fireModelEvent()
    {
        //
    }

    public function freshTimestamp()
    {
        return Carbon::now();
    }

    public function fromDateTime()
    {
        return 'date-time';
    }

    public function getUpdatedAtColumn()
    {
        return defined('static::UPDATED_AT') ? static::UPDATED_AT : 'updated_at';
    }

    public function setKeysForSaveQuery($query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    protected function getKeyForSaveQuery()
    {
        return 1;
    }
}
