<?php

use Mockery as m;
use Illuminate\Support\ServiceProvider;

class SupportServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $app = m::mock('Illuminate\\Foundation\\Application')->makePartial();
        $one = new ServiceProviderForTestingOne($app);
        $one->boot();
        $two = new ServiceProviderForTestingTwo($app);
        $two->boot();
    }

    public function tearDown()
    {
        m::close();
    }

    public function testSimpleAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish('ServiceProviderForTestingOne');
        $this->assertArrayHasKey('source/unmarked/one', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/tagged/one', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertEquals(['source/unmarked/one' => 'destination/unmarked/one', 'source/tagged/one' => 'destination/tagged/one'], $toPublish, 'Service provider does not return expected set of published paths.');
    }

    public function testMultipleAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish('ServiceProviderForTestingTwo');
        $this->assertArrayHasKey('source/unmarked/two/a', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/unmarked/two/b', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/tagged/two/a', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/tagged/two/b', $toPublish, 'Service provider does not return expected published path key.');
        $expected = [
            'source/unmarked/two/a' => 'destination/unmarked/two/a',
            'source/unmarked/two/b' => 'destination/unmarked/two/b',
            'source/tagged/two/a' => 'destination/tagged/two/a',
            'source/tagged/two/b' => 'destination/tagged/two/b',
        ];
        $this->assertEquals($expected, $toPublish, 'Service provider does not return expected set of published paths.');
    }

    public function testSimpleTaggedAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish('ServiceProviderForTestingOne', 'some_tag');
        $this->assertArrayNotHasKey('source/tagged/two/a', $toPublish, 'Service provider does return unexpected tagged path key.');
        $this->assertArrayNotHasKey('source/tagged/two/b', $toPublish, 'Service provider does return unexpected tagged path key.');
        $this->assertArrayHasKey('source/tagged/one', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertEquals(['source/tagged/one' => 'destination/tagged/one'], $toPublish, 'Service provider does not return expected set of published tagged paths.');
    }

    public function testMultipleTaggedAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish('ServiceProviderForTestingTwo', 'some_tag');
        $this->assertArrayHasKey('source/tagged/two/a', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertArrayHasKey('source/tagged/two/b', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertArrayNotHasKey('source/tagged/one', $toPublish, 'Service provider does return unexpected tagged path key.');
        $expected = [
            'source/tagged/two/a' => 'destination/tagged/two/a',
            'source/tagged/two/b' => 'destination/tagged/two/b',
        ];
        $this->assertEquals($expected, $toPublish, 'Service provider does not return expected set of published tagged paths.');
    }

    public function testMultipleTaggedAssetsAreMergedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish(null, 'some_tag');
        $this->assertArrayHasKey('source/tagged/two/a', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertArrayHasKey('source/tagged/two/b', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertArrayHasKey('source/tagged/one', $toPublish, 'Service provider does not return expected tagged path key.');
        $expected = [
            'source/tagged/one' => 'destination/tagged/one',
            'source/tagged/two/a' => 'destination/tagged/two/a',
            'source/tagged/two/b' => 'destination/tagged/two/b',
        ];
        $this->assertEquals($expected, $toPublish, 'Service provider does not return expected set of published tagged paths.');
    }

    public function testLoadViewsFromAddsOverridingDirectoryFromAppIfExists1()
    {
        $app = m::mock('Illuminate\\Foundation\\Application')->makePartial();
        $viewFinder = m::mock('Illuminate\\View\\FileViewFinder');
        $files = m::mock('Illuminate\\Filesystem\\Filesystem');
        $provider = new ServiceProviderForTestingThree($app);

        $app->shouldReceive('basePath')->once()->andReturn(__DIR__.'/zaz');
        $app->shouldReceive('offsetGet')->twice()->with('view')->andReturn($viewFinder);
        $app->shouldReceive('offsetGet')->once()->with('files')->andReturn($files);

        $viewFinder->shouldReceive('addNamespace')->once()->with('name.space', __DIR__.'/zaz/resources/views/vendor/name.space');
        $viewFinder->shouldReceive('addNamespace')->once()->with('name.space', __DIR__.'/foo');

        $files->shouldReceive('isDirectory')->once()->andReturn(true);

        $provider->boot();
    }

    public function testLoadViewsFromAddsOverridingDirectoryFromAppIfExists2()
    {
        $app = m::mock('Illuminate\\Foundation\\Application')->makePartial();
        $viewFinder = m::mock('Illuminate\\View\\FileViewFinder');
        $files = m::mock('Illuminate\\Filesystem\\Filesystem');
        $provider = new ServiceProviderForTestingThree($app);

        $app->shouldReceive('basePath')->once()->andReturn(__DIR__.'/zaz');
        $app->shouldReceive('offsetGet')->twice()->with('view')->andReturn($viewFinder);
        $app->shouldReceive('offsetGet')->twice()->with('files')->andReturn($files);

        $viewFinder->shouldReceive('addNamespace')->once()->with('name.space', __DIR__.'/zaz/resources/views/vendor/name/space');
        $viewFinder->shouldReceive('addNamespace')->once()->with('name.space', __DIR__.'/foo');

        $files->shouldReceive('isDirectory')->once()->with(__DIR__.'/zaz/resources/views/vendor/name.space')->andReturn(false);
        $files->shouldReceive('isDirectory')->once()->with(__DIR__.'/zaz/resources/views/vendor/name/space')->andReturn(true);

        $provider->boot();
    }
}

class ServiceProviderForTestingOne extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        $this->publishes(['source/unmarked/one' => 'destination/unmarked/one']);
        $this->publishes(['source/tagged/one' => 'destination/tagged/one'], 'some_tag');
    }
}

class ServiceProviderForTestingTwo extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        $this->publishes(['source/unmarked/two/a' => 'destination/unmarked/two/a']);
        $this->publishes(['source/unmarked/two/b' => 'destination/unmarked/two/b']);
        $this->publishes(['source/tagged/two/a' => 'destination/tagged/two/a'], 'some_tag');
        $this->publishes(['source/tagged/two/b' => 'destination/tagged/two/b'], 'some_tag');
    }
}

class ServiceProviderForTestingThree extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/foo', 'name.space');
    }
}
