<?php

namespace Illuminate\Tests\Translation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TranslationFileLoaderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testLoadMethodWithoutNamespacesProperlyCallsLoader()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/en/foo.php')->andReturn(true);
        $files->shouldReceive('getRequire')->once()->with(__DIR__.'/en/foo.php')->andReturn(['messages']);

        $this->assertEquals(['messages'], $loader->load('en', 'foo', null));
    }

    public function testLoadMethodWithoutNamespacesProperlyCallsLoaderWithMultiplePaths()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), [__DIR__, __DIR__.'/second']);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/en/foo.php')->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/second/en/foo.php')->andReturn(true);
        $files->shouldReceive('getRequire')->once()->with(__DIR__.'/en/foo.php')->andReturn(['messages' => 'first']);
        $files->shouldReceive('getRequire')->once()->with(__DIR__.'/second/en/foo.php')->andReturn(['messages' => 'second']);

        $this->assertEquals(['messages' => 'second'], $loader->load('en', 'foo', null));
    }

    public function testLoadMethodWithNamespacesProperlyCallsLoader()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), __DIR__);
        $files->shouldReceive('exists')->once()->with('bar/en/foo.php')->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(false);
        $files->shouldReceive('getRequire')->once()->with('bar/en/foo.php')->andReturn(['foo' => 'bar']);
        $loader->addNamespace('namespace', 'bar');

        $this->assertEquals(['foo' => 'bar'], $loader->load('en', 'foo', 'namespace'));
    }

    public function testLoadMethodWithNamespacesProperlyCallsLoaderWithMultiplePaths()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), [__DIR__, __DIR__.'/second']);
        $files->shouldReceive('exists')->once()->with('test-namespace-dir/en/foo.php')->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(false);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/second/vendor/namespace/en/foo.php')->andReturn(false);
        $files->shouldReceive('getRequire')->once()->with('test-namespace-dir/en/foo.php')->andReturn(['foo' => 'bar']);
        $loader->addNamespace('namespace', 'test-namespace-dir');

        $this->assertEquals(['foo' => 'bar'], $loader->load('en', 'foo', 'namespace'));
    }

    public function testLoadMethodWithNamespacesProperlyCallsLoaderAndLoadsLocalOverrides()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), __DIR__);
        $files->shouldReceive('exists')->once()->with('bar/en/foo.php')->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(true);
        $files->shouldReceive('getRequire')->once()->with('bar/en/foo.php')->andReturn(['foo' => 'bar']);
        $files->shouldReceive('getRequire')->once()->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(['foo' => 'override', 'baz' => 'boom']);
        $loader->addNamespace('namespace', 'bar');

        $this->assertEquals(['foo' => 'override', 'baz' => 'boom'], $loader->load('en', 'foo', 'namespace'));
    }

    public function testLoadMethodWithNamespacesProperlyCallsLoaderAndLoadsLocalOverridesWithMultiplePaths()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), [__DIR__, __DIR__.'/second']);
        $files->shouldReceive('exists')->once()->with('test-namespace-dir/en/foo.php')->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/second/vendor/namespace/en/foo.php')->andReturn(true);
        $files->shouldReceive('getRequire')->once()->with('test-namespace-dir/en/foo.php')->andReturn(['foo' => 'bar']);
        $files->shouldReceive('getRequire')->once()->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(['foo' => 'override', 'baz' => 'boom']);
        $files->shouldReceive('getRequire')->once()->with(__DIR__.'/second/vendor/namespace/en/foo.php')->andReturn(['foo' => 'override-2', 'baz' => 'boom-2']);
        $loader->addNamespace('namespace', 'test-namespace-dir');

        $this->assertEquals(['foo' => 'override-2', 'baz' => 'boom-2'], $loader->load('en', 'foo', 'namespace'));
    }

    public function testEmptyArraysReturnedWhenFilesDontExist()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/en/foo.php')->andReturn(false);
        $files->shouldReceive('getRequire')->never();

        $this->assertEquals([], $loader->load('en', 'foo', null));
    }

    public function testEmptyArraysReturnedWhenFilesDontExistForNamespacedItems()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), __DIR__);
        $files->shouldReceive('getRequire')->never();

        $this->assertEquals([], $loader->load('en', 'foo', 'bar'));
    }

    public function testLoadMethodForJSONProperlyCallsLoader()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/en.json')->andReturn(true);
        $files->shouldReceive('get')->once()->with(__DIR__.'/en.json')->andReturn('{"foo":"bar"}');

        $this->assertEquals(['foo' => 'bar'], $loader->load('en', '*', '*'));
    }

    public function testLoadMethodForJSONProperlyCallsLoaderForMultiplePaths()
    {
        $loader = new FileLoader($files = m::mock(Filesystem::class), __DIR__);
        $loader->addJsonPath(__DIR__.'/another');

        $files->shouldReceive('exists')->once()->with(__DIR__.'/en.json')->andReturn(true);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/another/en.json')->andReturn(true);
        $files->shouldReceive('get')->once()->with(__DIR__.'/en.json')->andReturn('{"foo":"bar"}');
        $files->shouldReceive('get')->once()->with(__DIR__.'/another/en.json')->andReturn('{"foo":"backagebar", "baz": "backagesplash"}');

        $this->assertEquals(['foo' => 'bar', 'baz' => 'backagesplash'], $loader->load('en', '*', '*'));
    }
}
