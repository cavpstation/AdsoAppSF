<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Publishing\ViewPublisher;
use Illuminate\Foundation\Publishing\AssetPublisher;
use Illuminate\Foundation\Publishing\ConfigPublisher;
use Illuminate\Foundation\Console\ViewPublishCommand;
use Illuminate\Foundation\Console\AssetPublishCommand;
use Illuminate\Foundation\Console\ConfigPublishCommand;
use Illuminate\Foundation\Publishing\MigrationPublisher;
use Illuminate\Foundation\Console\MigratePublishCommand;

class PublisherServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerAssetPublisher();

		$this->registerConfigPublisher();

		$this->registerViewPublisher();

		$this->registerMigrationPublisher();

		$this->registerPackageRegistrar();

		$this->commands(
			'command.asset.publish', 'command.config.publish',
			'command.view.publish', 'command.migrate.publish'
		);
	}

	/**
	 * Register the asset publisher service and command.
	 *
	 * @return void
	 */
	protected function registerAssetPublisher()
	{
		$this->registerAssetPublishCommand();

		$this->app->singleton('asset.publisher', function($app)
		{
			$publicPath = $app['path.public'];

			// The asset "publisher" is responsible for moving package's assets into the
			// web accessible public directory of an application so they can actually
			// be served to the browser. Otherwise, they would be locked in vendor.
			$publisher = new AssetPublisher($app['files'], $app['registrar.packages'], $publicPath);

			$publisher->setPackagePath($app['path.base'].'/vendor');

			return $publisher;
		});
	}

	/**
	 * Register the asset publish console command.
	 *
	 * @return void
	 */
	protected function registerAssetPublishCommand()
	{
		$this->app->singleton('command.asset.publish', function($app)
		{
			return new AssetPublishCommand($app['asset.publisher']);
		});
	}

	/**
	 * Register the configuration publisher class and command.
	 *
	 * @return void
	 */
	protected function registerConfigPublisher()
	{
		$this->registerConfigPublishCommand();

		$this->app->singleton('config.publisher', function($app)
		{
			$path = $app['path.config'];

			// Once we have created the configuration publisher, we will set the default
			// package path on the object so that it knows where to find the packages
			// that are installed for the application and can move them to the app.
			$publisher = new ConfigPublisher($app['files'], $app['registrar.packages'], $path);

			$publisher->setPackagePath($app['path.base'].'/vendor');

			return $publisher;
		});
	}

	/**
	 * Register the configuration publish console command.
	 *
	 * @return void
	 */
	protected function registerConfigPublishCommand()
	{
		$this->app->singleton('command.config.publish', function($app)
		{
			return new ConfigPublishCommand($app['config.publisher']);
		});
	}

	/**
	 * Register the view publisher class and command.
	 *
	 * @return void
	 */
	protected function registerViewPublisher()
	{
		$this->registerViewPublishCommand();

		$this->app->singleton('view.publisher', function($app)
		{
			$viewPath = $app['path.base'].'/resources/views';

			// Once we have created the view publisher, we will set the default packages
			// path on this object so that it knows where to find all of the packages
			// that are installed for the application and can move them to the app.
			$publisher = new ViewPublisher($app['files'], $app['registrar.packages'], $viewPath);

			$publisher->setPackagePath($app['path.base'].'/vendor');

			return $publisher;
		});
	}

	/**
	 * Register the view publish console command.
	 *
	 * @return void
	 */
	protected function registerViewPublishCommand()
	{
		$this->app->singleton('command.view.publish', function($app)
		{
			return new ViewPublishCommand($app['view.publisher']);
		});
	}

	/**
	 * Register the migration publisher class and command.
	 *
	 * @return void
	 */
	protected function registerMigrationPublisher()
	{
		$this->registerMigratePublishCommand();

		$this->app->singleton('migration.publisher', function($app)
		{
			return new MigrationPublisher($app['files'], $app['registrar.packages']);
		});
	}

	/**
	 * Register the migration publisher command.
	 *
	 * @return void
	 */
	protected function registerMigratePublishCommand()
	{
		$this->app->singleton('command.migrate.publish', function()
		{
			return new MigratePublishCommand;
		});
	}

	/**
	 * Register the package registrar.
	 *
	 * @return void
	 */
	protected function registerPackageRegistrar()
	{
		$this->app->singleton('registrar.packages', function()
		{
			return new PackageRegistrar();
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(
			'asset.publisher',
			'command.asset.publish',
			'config.publisher',
			'command.config.publish',
			'view.publisher',
			'command.view.publish',
			'migration.publisher',
			'command.migrate.publish',
		);
	}

}
