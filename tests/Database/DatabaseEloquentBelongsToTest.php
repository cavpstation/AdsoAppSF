<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseEloquentBelongsToTest extends TestCase
{
    protected $builder;

    protected $related;

    public function tearDown(): void
    {
        m::close();
    }

    public function testBelongsToWithDefault(): void
    {
        $relation = $this->getRelation()->withDefault(); //belongsTo relationships

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentBelongsToModelStub();  //ie Blog

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());
    }

    public function testBelongsToWithDynamicDefault(): void
    {
        $relation = $this->getRelation()->withDefault(function ($newModel) {
            $newModel->username = 'taylor';
        });

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentBelongsToModelStub();

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);
    }

    public function testBelongsToWithArrayDefault(): void
    {
        $relation = $this->getRelation()->withDefault(['username' => 'taylor']);

        $this->builder->shouldReceive('first')->once()->andReturnNull();

        $newModel = new EloquentBelongsToModelStub();

        $this->related->shouldReceive('newInstance')->once()->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);
    }

    public function testUpdateMethodRetrievesModelAndUpdates(): void
    {
        $relation = $this->getRelation();
        $mock = m::mock('Illuminate\Database\Eloquent\Model');
        $mock->shouldReceive('fill')->once()->with(['attributes'])->andReturn($mock);
        $mock->shouldReceive('save')->once()->andReturn(true);
        $relation->getQuery()->shouldReceive('first')->once()->andReturn($mock);

        $this->assertTrue($relation->update(['attributes']));
    }

    public function testEagerConstraintsAreProperlyAdded(): void
    {
        $relation = $this->getRelation();
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.id', ['foreign.value', 'foreign.value.two']);
        $models = [new EloquentBelongsToModelStub, new EloquentBelongsToModelStub, new AnotherEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testIdsInEagerConstraintsCanBeZero(): void
    {
        $relation = $this->getRelation();
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.id', ['foreign.value', 0]);
        $models = [new EloquentBelongsToModelStub, new EloquentBelongsToModelStubWithZeroId];
        $relation->addEagerConstraints($models);
    }

    public function testRelationIsProperlyInitialized(): void
    {
        $relation = $this->getRelation();
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('setRelation')->once()->with('foo', null);
        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    public function testModelsAreProperlyMatchedToParents(): void
    {
        $relation = $this->getRelation();
        $result1 = m::mock('stdClass');
        $result1->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $result2 = m::mock('stdClass');
        $result2->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $model1 = new EloquentBelongsToModelStub;
        $model1->foreign_key = 1;
        $model2 = new EloquentBelongsToModelStub;
        $model2->foreign_key = 2;
        $models = $relation->match([$model1, $model2], new Collection([$result1, $result2]), 'foo');

        $this->assertEquals(1, $models[0]->foo->getAttribute('id'));
        $this->assertEquals(2, $models[1]->foo->getAttribute('id'));
    }

    public function testAssociateMethodSetsForeignKeyOnModel(): void
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model');
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        $relation = $this->getRelation($parent);
        $associate = m::mock('Illuminate\Database\Eloquent\Model');
        $associate->shouldReceive('getAttribute')->once()->with('id')->andReturn(1);
        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', 1);
        $parent->shouldReceive('setRelation')->once()->with('relation', $associate);

        $relation->associate($associate);
    }

    public function testDissociateMethodUnsetsForeignKeyOnModel(): void
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model');
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        $relation = $this->getRelation($parent);
        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', null);
        $parent->shouldReceive('setRelation')->once()->with('relation', null);
        $relation->dissociate();
    }

    public function testAssociateMethodSetsForeignKeyOnModelById(): void
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model');
        $parent->shouldReceive('getAttribute')->once()->with('foreign_key')->andReturn('foreign.value');
        $relation = $this->getRelation($parent);
        $parent->shouldReceive('setAttribute')->once()->with('foreign_key', 1);
        $relation->associate(1);
    }

    public function testDefaultEagerConstraintsWhenIncrementing(): void
    {
        $relation = $this->getRelation();
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.id', m::mustBe([null]));
        $models = [new MissingEloquentBelongsToModelStub, new MissingEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testDefaultEagerConstraintsWhenIncrementingAndNonIntKeyType(): void
    {
        $relation = $this->getRelation(null, false, 'string');
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.id', m::mustBe([null]));
        $models = [new MissingEloquentBelongsToModelStub, new MissingEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    public function testDefaultEagerConstraintsWhenNotIncrementing(): void
    {
        $relation = $this->getRelation(null, false);
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('relation.id', m::mustBe([null]));
        $models = [new MissingEloquentBelongsToModelStub, new MissingEloquentBelongsToModelStub];
        $relation->addEagerConstraints($models);
    }

    protected function getRelation($parent = null, $incrementing = true, $keyType = 'int')
    {
        $this->builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $this->builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
        $this->related = m::mock('Illuminate\Database\Eloquent\Model');
        $this->related->incrementing = $incrementing;
        $this->related->shouldReceive('getKeyType')->andReturn($keyType);
        $this->related->shouldReceive('getIncrementing')->andReturn($incrementing);
        $this->related->shouldReceive('getKeyName')->andReturn('id');
        $this->related->shouldReceive('getTable')->andReturn('relation');
        $this->builder->shouldReceive('getModel')->andReturn($this->related);
        $parent = $parent ?: new EloquentBelongsToModelStub;

        return new BelongsTo($this->builder, $parent, 'foreign_key', 'id', 'relation');
    }
}

class EloquentBelongsToModelStub extends \Illuminate\Database\Eloquent\Model
{
    public $foreign_key = 'foreign.value';
}

class AnotherEloquentBelongsToModelStub extends \Illuminate\Database\Eloquent\Model
{
    public $foreign_key = 'foreign.value.two';
}

class EloquentBelongsToModelStubWithZeroId extends \Illuminate\Database\Eloquent\Model
{
    public $foreign_key = 0;
}

class MissingEloquentBelongsToModelStub extends \Illuminate\Database\Eloquent\Model
{
    public $foreign_key;
}
