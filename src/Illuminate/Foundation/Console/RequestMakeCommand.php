<?php namespace Illuminate\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class RequestMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:request';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new form request class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Request';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/request.stub';
	}

}
