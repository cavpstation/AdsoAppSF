<?php

namespace Illuminate\Tests\Auth;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\EloquentUserProvider;

class AuthEloquentUserProviderTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRetrieveByIDReturnsUser()
    {
        $provider = $this->getProviderMock();
        $mock = m::mock(\stdClass::class);
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $mock->shouldReceive('where')->once()->with('id', 1)->andReturn($mock);
        $mock->shouldReceive('first')->once()->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
        $user = $provider->retrieveById(1);

        $this->assertEquals('bar', $user);
    }

    public function testRetrieveByCredentialsReturnsUser()
    {
        $provider = $this->getProviderMock();
        $mock = m::mock(\stdClass::class);
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('where')->once()->with('username', 'dayle');
        $mock->shouldReceive('first')->once()->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
        $user = $provider->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo']);

        $this->assertEquals('bar', $user);
    }

    public function testCredentialValidation()
    {
        $conn = m::mock(\Illuminate\Database\Connection::class);
        $hasher = m::mock(\Illuminate\Contracts\Hashing\Hasher::class);
        $hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(true);
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = m::mock(\Illuminate\Contracts\Auth\Authenticatable::class);
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }

    public function testModelsCanBeCreated()
    {
        $hasher = m::mock(\Illuminate\Contracts\Hashing\Hasher::class);
        $provider = new EloquentUserProvider($hasher, \Illuminate\Tests\Auth\EloquentProviderUserStub::class);
        $model = $provider->createModel();

        $this->assertInstanceOf(\Illuminate\Tests\Auth\EloquentProviderUserStub::class, $model);
    }

    protected function getProviderMock()
    {
        $hasher = m::mock(\Illuminate\Contracts\Hashing\Hasher::class);

        return $this->getMockBuilder(\Illuminate\Auth\EloquentUserProvider::class)->setMethods(['createModel'])->setConstructorArgs([$hasher, 'foo'])->getMock();
    }
}

class EloquentProviderUserStub
{
}
