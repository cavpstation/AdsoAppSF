<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

class FoundationApplicationTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSetLocaleSetsLocaleAndFiresLocaleChangedEvent()
	{
		$app = new Application;
		$app['config'] = $config = m::mock('StdClass');
		$config->shouldReceive('set')->once()->with('app.locale', 'foo');
		$app['translator'] = $trans = m::mock('StdClass');
		$trans->shouldReceive('setLocale')->once()->with('foo');
		$app['events'] = $events = m::mock('StdClass');
		$events->shouldReceive('fire')->once()->with('locale.changed', array('foo'));

		$app->setLocale('foo');
	}


	public function testServiceProvidersAreCorrectlyRegistered()
	{
		$provider = m::mock('Illuminate\Support\ServiceProvider');
		$class = get_class($provider);
		$provider->shouldReceive('register')->once();
		$app = new Application;
		$app->register($provider);

		$this->assertTrue(in_array($class, $app->getLoadedProviders()));
	}


	public function testDeferredServicesMarkedAsBound()
	{
		$app = new Application;
		$app->setDeferredServices(array('foo' => 'ApplicationDeferredServiceProviderStub'));
		$this->assertTrue($app->bound('foo'));
		$this->assertEquals('foo', $app->make('foo'));
	}


	public function testDeferredServicesCanBeExtended()
	{
		$app = new Application;
		$app->setDeferredServices(array('foo' => 'ApplicationDeferredServiceProviderStub'));
		$app->extend('foo', function($instance, $container) { return $instance.'bar'; });
		$this->assertEquals('foobar', $app->make('foo'));
	}


	public function testDeferredServiceProviderIsRegisteredOnlyOnce()
	{
		$app = new Application;
		$app->setDeferredServices(array('foo' => 'ApplicationDeferredServiceProviderCountStub'));
		$obj = $app->make('foo');
		$this->assertInstanceOf('StdClass', $obj);
		$this->assertSame($obj, $app->make('foo'));
		$this->assertEquals(1, ApplicationDeferredServiceProviderCountStub::$count);
	}


	public function testDeferredServicesAreLazilyInitialized()
	{
		$app = new Application;
		$app->setDeferredServices(array('foo' => 'ApplicationLazyDeferredServiceProviderStub'));
		$this->assertTrue($app->bound('foo'));
		$this->assertFalse(ApplicationLazyDeferredServiceProviderStub::$initialized);
		$app->extend('foo', function($instance, $container) { return $instance.'bar'; });
		$this->assertFalse(ApplicationLazyDeferredServiceProviderStub::$initialized);
		$this->assertEquals('foobar', $app->make('foo'));
		$this->assertTrue(ApplicationLazyDeferredServiceProviderStub::$initialized);
	}

}

class ApplicationCustomExceptionHandlerStub extends Illuminate\Foundation\Application {

	public function prepareResponse($value)
	{
		$response = m::mock('Symfony\Component\HttpFoundation\Response');
		$response->shouldReceive('send')->once();
		return $response;
	}

	protected function setExceptionHandler(Closure $handler) { return $handler; }

}

class ApplicationKernelExceptionHandlerStub extends Illuminate\Foundation\Application {

	protected function setExceptionHandler(Closure $handler) { return $handler; }

}

class ApplicationDeferredServiceProviderStub extends Illuminate\Support\ServiceProvider {
	protected $defer = true;
	public function register()
	{
		$this->app['foo'] = 'foo';
	}
}

class ApplicationDeferredServiceProviderCountStub extends Illuminate\Support\ServiceProvider {
	public static $count = 0;
	protected $defer = true;
	public function register()
	{
		static::$count++;
		$this->app['foo'] = new StdClass;
	}
}

class ApplicationLazyDeferredServiceProviderStub extends Illuminate\Support\ServiceProvider {
	public static $initialized = false;
	protected $defer = true;
	public function register()
	{
		static::$initialized = true;
		$this->app['foo'] = 'foo';
	}
}
