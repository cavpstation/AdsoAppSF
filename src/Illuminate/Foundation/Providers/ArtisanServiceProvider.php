<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Foundation\Console\TinkerCommand;
use Illuminate\Foundation\Console\AppNameCommand;
use Illuminate\Foundation\Console\OptimizeCommand;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Foundation\Console\EventMakeCommand;
use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Foundation\Console\RouteCacheCommand;
use Illuminate\Foundation\Console\RouteClearCommand;
use Illuminate\Foundation\Console\CommandMakeCommand;
use Illuminate\Foundation\Console\ConfigCacheCommand;
use Illuminate\Foundation\Console\ConfigClearCommand;
use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Foundation\Console\RequestMakeCommand;
use Illuminate\Foundation\Console\ProviderMakeCommand;
use Illuminate\Foundation\Console\HandlerEventCommand;
use Illuminate\Foundation\Console\ClearCompiledCommand;
use Illuminate\Foundation\Console\VendorPublishCommand;
use Illuminate\Foundation\Console\HandlerCommandCommand;

class ArtisanServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * The commands to be registered.
	 *
	 * @var array
	 */
	protected $commands = [
		'AppName' => 'command.app.name',
		'ClearCompiled' => 'command.clear-compiled',
		'CommandMake' => 'command.command.make',
		'ConfigCache' => 'command.config.cache',
		'ConfigClear' => 'command.config.clear',
		'ConsoleMake' => 'command.console.make',
		'EventMake' => 'command.event.make',
		'Down' => 'command.down',
		'Environment' => 'command.environment',
		'HandlerCommand' => 'command.handler.command',
		'HandlerEvent' => 'command.handler.event',
		'KeyGenerate' => 'command.key.generate',
		'ModelMake' => 'command.model.make',
		'Optimize' => 'command.optimize',
		'ProviderMake' => 'command.provider.make',
		'RequestMake' => 'command.request.make',
		'RouteCache' => 'command.route.cache',
		'RouteClear' => 'command.route.clear',
		'RouteList' => 'command.route.list',
		'Serve' => 'command.serve',
		'Tinker' => 'command.tinker',
		'Up' => 'command.up',
		'VendorPublish' => 'command.vendor.publish',
	];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		foreach (array_keys($this->commands) as $command)
		{
			$method = "register{$command}Command";

			call_user_func_array([$this, $method], []);
		}

		$this->commands(array_values($this->commands));
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerAppNameCommand()
	{
		$this->app->singleton('command.app.name', function()
		{
			return new AppNameCommand($this->app['composer'], $this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerClearCompiledCommand()
	{
		$this->app->singleton('command.clear-compiled', function()
		{
			return new ClearCompiledCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerCommandMakeCommand()
	{
		$this->app->singleton('command.command.make', function()
		{
			return new CommandMakeCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerConfigCacheCommand()
	{
		$this->app->singleton('command.config.cache', function()
		{
			return new ConfigCacheCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerConfigClearCommand()
	{
		$this->app->singleton('command.config.clear', function()
		{
			return new ConfigClearCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerConsoleMakeCommand()
	{
		$this->app->singleton('command.console.make', function()
		{
			return new ConsoleMakeCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerEventMakeCommand()
	{
		$this->app->singleton('command.event.make', function()
		{
			return new EventMakeCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerDownCommand()
	{
		$this->app->singleton('command.down', function()
		{
			return new DownCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerEnvironmentCommand()
	{
		$this->app->singleton('command.environment', function()
		{
			return new EnvironmentCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerHandlerCommandCommand()
	{
		$this->app->singleton('command.handler.command', function()
		{
			return new HandlerCommandCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerHandlerEventCommand()
	{
		$this->app->singleton('command.handler.event', function()
		{
			return new HandlerEventCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerKeyGenerateCommand()
	{
		$this->app->singleton('command.key.generate', function()
		{
			return new KeyGenerateCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerModelMakeCommand()
	{
		$this->app->singleton('command.model.make', function()
		{
			return new ModelMakeCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerOptimizeCommand()
	{
		$this->app->singleton('command.optimize', function()
		{
			return new OptimizeCommand($this->app['composer']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerProviderMakeCommand()
	{
		$this->app->singleton('command.provider.make', function()
		{
			return new ProviderMakeCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRequestMakeCommand()
	{
		$this->app->singleton('command.request.make', function()
		{
			return new RequestMakeCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRouteCacheCommand()
	{
		$this->app->singleton('command.route.cache', function()
		{
			return new RouteCacheCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRouteClearCommand()
	{
		$this->app->singleton('command.route.clear', function()
		{
			return new RouteClearCommand($this->app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRouteListCommand()
	{
		$this->app->singleton('command.route.list', function()
		{
			return new RouteListCommand($this->app['router']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerServeCommand()
	{
		$this->app->singleton('command.serve', function()
		{
			return new ServeCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerTinkerCommand()
	{
		$this->app->singleton('command.tinker', function()
		{
			return new TinkerCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerUpCommand()
	{
		$this->app->singleton('command.up', function()
		{
			return new UpCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerVendorPublishCommand()
	{
		$this->app->singleton('command.vendor.publish', function($app)
		{
			return new VendorPublishCommand($app['files']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array_values($this->commands);
	}

}
