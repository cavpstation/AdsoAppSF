<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentModelCustomCastingTest extends DatabaseTestCase
{
    public function testFoo()
    {
        $item = TestModel::create([
            'field_1' => 'foobar',
            'field_2' => 20,
            'field_3' => '08:19:12',
            'field_4' => null,
            'field_5' => null,
        ]);

        $this->assertSame(['f', 'o', 'o', 'b', 'a', 'r'], $item->toArray()['field_1']);

        $this->assertSame(0.2, $item->toArray()['field_2']);

        $this->assertIsNumeric($item->toArray()['field_3']);

        $this->assertSame(
            strtotime('08:19:12'),
            $item->toArray()['field_3']
        );

        $this->assertSame(null, $item->toArray()['field_4']);

        $this->assertSame('foo', $item->toArray()['field_5']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('field_1')->nullable();
            $table->integer('field_2')->nullable();
            $table->time('field_3')->nullable();
            $table->string('field_4')->nullable();
            $table->string('field_5')->nullable();
        });
    }
}

class TestModel extends Model
{
    public $table = 'test_model1';

    public $timestamps = false;

    public $casts = [
        'field_1' => StringCast::class,
        'field_2' => NumberCast::class,
        'field_3' => TimeCast::class,
        'field_4' => NullCast::class,
        'field_5' => NullChangedCast::class,
    ];

    protected $guarded = ['id'];
}

class TimeCast implements Castable
{
    public function get($value = null)
    {
        return strtotime($value);
    }

    public function set($value = null)
    {
        return is_numeric($value)
            ? date('H:i:s', strtotime($value))
            : $value;
    }
}

class StringCast implements Castable
{
    public function get($value = null)
    {
        return str_split($value);
    }

    public function set($value = null)
    {
        return is_array($value)
            ? implode('', $value)
            : $value;
    }
}

class NumberCast implements Castable
{
    public function get($value = null)
    {
        return $value / 100;
    }

    public function set($value = null)
    {
        return $value;
    }
}

class NullCast implements Castable
{
    public function get($value = null)
    {
        return $value;
    }

    public function set($value = null)
    {
        return null;
    }
}

class NullChangedCast implements Castable
{
    public function get($value = null)
    {
        return 'foo';
    }

    public function set($value = null)
    {
        return null;
    }
}
