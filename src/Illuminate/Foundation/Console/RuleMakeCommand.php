<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:rule')]
class RuleMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:rule';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     * @deprecated
     */
    protected static $defaultName = 'make:rule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new validation rule';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Rule';

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        return str_replace(
            '{{ ruleType }}',
            $this->option('implicit') ? 'ImplicitRule' : 'Rule',
            parent::buildClass($name)
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $relativePath = '/stubs/rule.stub';

        return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__.$relativePath;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Rules';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['implicit', 'i', InputOption::VALUE_NONE, 'Generate an implicit rule.'],
        ];
    }
}
