<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class AppNameCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'app:name';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Set the application namespace";

	/**
	 * The Composer class instance.
	 *
	 * @var \Illuminate\Foundation\Composer
	 */
	protected $composer;

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Current root application namespace.
	 *
	 * @var string
	 */
	protected $currentRoot;

	/**
	 * Create a new key generator command.
	 *
	 * @param  \Illuminate\Foundation\Composer  $composer
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Composer $composer, Filesystem $files)
	{
		parent::__construct();

		$this->files = $files;
		$this->composer = $composer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if ( ! $this->validNamespace())
		{
			return $this->error('Invalid application name.');
		}
		elseif ($this->namespaceExists())
		{
			return $this->error('This name is already used in a namespace.');
		}

		$this->currentRoot = trim($this->laravel->getNamespace(), '\\');

		$this->setBootstrapNamespaces();

		$this->setAppDirectoryNamespace();

		$this->setConfigNamespaces();

		$this->setComposerNamespace();

		$this->setPhpSpecNamespace();

		$this->info('Application namespace set!');

		$this->composer->dumpAutoloads();

		$this->call('clear-compiled');
	}

	/**
	 * Determine if the name is a valid namespace.
	 *
	 * @return bool
	 */
	protected function validNamespace()
	{
		return preg_match('/^[a-z_]\w+$/i', $this->argument('name'));
	}

	/**
	 * Determine if there is already a namespace with same name.
	 *
	 * @return bool
	 */
	protected function namespaceExists()
	{
		$namespace = $this->argument('name') . "\\";

                foreach(get_declared_classes() as $name)
		{
    	        	if(strpos($name, $namespace) === 0) return true;
		}

    		return false;
	}

	/**
	 * Set the namespace on the files in the app directory.
	 *
	 * @return void
	 */
	protected function setAppDirectoryNamespace()
	{
		$files = Finder::create()
                            ->in($this->laravel['path'])
                            ->name('*.php');

		foreach ($files as $file)
		{
			$this->replaceNamespace($file->getRealPath());
		}
	}

	/**
	 * Replace the App namespace at the given path.
	 *
	 * @param  string  $path
	 */
	protected function replaceNamespace($path)
	{
		$search = [
			'namespace '.$this->currentRoot.';',
			$this->currentRoot.'\\',
		];

		$replace = [
			'namespace '.$this->argument('name').';',
			$this->argument('name').'\\',
		];

		$this->replaceIn($path, $search, $replace);
	}

	/**
	 * Set the bootstrap namespaces.
	 *
	 * @return void
	 */
	protected function setBootstrapNamespaces()
	{
		$search = [
			$this->currentRoot.'\\Http',
			$this->currentRoot.'\\Console',
			$this->currentRoot.'\\Exceptions',
		];

		$replace = [
			$this->argument('name').'\\Http',
			$this->argument('name').'\\Console',
			$this->argument('name').'\\Exceptions',
		];

		$this->replaceIn($this->getBootstrapPath(), $search, $replace);
	}

	/**
	 * Set the PSR-4 namespace in the Composer file.
	 *
	 * @return void
	 */
	protected function setComposerNamespace()
	{
		$this->replaceIn(
			$this->getComposerPath(), $this->currentRoot.'\\\\', str_replace('\\', '\\\\', $this->argument('name')).'\\\\'
		);
	}

	/**
	 * Set the namespace in the appropriate configuration files.
	 *
	 * @return void
	 */
	protected function setConfigNamespaces()
	{
		$this->setAppConfigNamespaces();

		$this->setAuthConfigNamespace();

		$this->setServicesConfigNamespace();
	}

	/**
	 * Set the application provider namespaces.
	 *
	 * @return void
	 */
	protected function setAppConfigNamespaces()
	{
		$search = [
			$this->currentRoot.'\\Providers',
			$this->currentRoot.'\\Http\\Controllers\\',
		];

		$replace = [
			$this->argument('name').'\\Providers',
			$this->argument('name').'\\Http\\Controllers\\',
		];

		$this->replaceIn($this->getConfigPath('app'), $search, $replace);
	}

	/**
	 * Set the authentication User namespace.
	 *
	 * @return void
	 */
	protected function setAuthConfigNamespace()
	{
		$this->replaceIn(
			$this->getAuthConfigPath(), $this->currentRoot.'\\User', $this->argument('name').'\\User'
		);
	}

	/**
	 * Set the services User namespace.
	 *
	 * @return void
	 */
	protected function setServicesConfigNamespace()
	{
		$this->replaceIn(
			$this->getServicesConfigPath(), $this->currentRoot.'\\User', $this->argument('name').'\\User'
		);
	}

	/**
	 * Set the PHPSpec configuration namespace.
	 *
	 * @return void
	 */
	protected function setPhpSpecNamespace()
	{
		if ($this->files->exists($path = $this->getPhpSpecConfigPath()))
		{
			$this->replaceIn($path, $this->currentRoot, $this->argument('name'));
		}
	}

	/**
	 * Replace the given string in the given file.
	 *
	 * @param  string  $path
	 * @param  string|array  $search
	 * @param  string|array  $replace
	 * @return void
	 */
	protected function replaceIn($path, $search, $replace)
	{
		$this->files->put($path, str_replace($search, $replace, $this->files->get($path)));
	}

	/**
	 * Get the path to the Core User class.
	 *
	 * @return string
	 */
	protected function getUserClassPath()
	{
		return $this->laravel['path'].'/Core/User.php';
	}

	/**
	 * Get the path to the bootstrap/app.php file.
	 *
	 * @return string
	 */
	protected function getBootstrapPath()
	{
		return $this->laravel->basePath().'/bootstrap/app.php';
	}

	/**
	 * Get the path to the Composer.json file.
	 *
	 * @return string
	 */
	protected function getComposerPath()
	{
		return $this->laravel->basePath().'/composer.json';
	}

	/**
	 * Get the path to the given configuration file.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getConfigPath($name)
	{
		return $this->laravel['path.config'].'/'.$name.'.php';
	}

	/**
	 * Get the path to the authentication configuration file.
	 *
	 * @return string
	 */
	protected function getAuthConfigPath()
	{
		return $this->getConfigPath('auth');
	}

	/**
	 * Get the path to the services configuration file.
	 *
	 * @return string
	 */
	protected function getServicesConfigPath()
	{
		return $this->getConfigPath('services');
	}

	/**
	 * Get the path to the PHPSpec configuration file.
	 *
	 * @return string
	 */
	protected function getPhpSpecConfigPath()
	{
		return $this->laravel->basePath().'/phpspec.yml';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The desired namespace.'),
		);
	}

}
