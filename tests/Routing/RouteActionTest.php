<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\RouteAction;
use Laravel\SerializableClosure\SerializableClosure;
use Opis\Closure\SerializableClosure as OpisSerializableClosure;
use PHPUnit\Framework\TestCase;

class RouteActionTest extends TestCase
{
    public function testItCanDetectASerializedClosure()
    {
        $callable = function (RouteActionUser $user) {
            return $user;
        };

        $action = ['uses' => serialize(\PHP_VERSION_ID < 70400
            ? new OpisSerializableClosure($callable)
            : new SerializableClosure($callable)
        )];

        $this->assertTrue(RouteAction::containsSerializedClosure($action));

        $action = ['uses' => 'FooController@index'];

        $this->assertFalse(RouteAction::containsSerializedClosure($action));
    }
}

class RouteActionUser extends Model
{
    //
}
