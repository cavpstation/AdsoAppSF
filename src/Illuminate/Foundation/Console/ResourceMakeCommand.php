<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:resource')]
class ResourceMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->collection() && ! $this->option('all')) {
            $this->createResource();
        }

        if ($this->option('all')) {
            $this->createResource();
            $this->input->setOption('collection', true);
        }

        if ($this->collection()) {
            $this->createCollection();
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->collection()
                    ? $this->resolveStubPath('/stubs/resource-collection.stub')
                    : $this->resolveStubPath('/stubs/resource.stub');
    }

    /**
     * Determine if the command is generating a resource collection.
     *
     * @return bool
     */
    protected function collection()
    {
        return $this->option('collection') ||
               str_ends_with($this->argument('name'), 'Collection');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
                        ? $customPath
                        : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Resources';
    }

    /**
     * Create a resource for the model.
     */
    protected function createResource()
    {
        parent::handle();
    }

    /**
     * Create a resource collection for the model.
     */
    protected function createCollection()
    {
        $this->type = 'Resource collection';

        $baseName = str_ends_with($this->argument('name'), 'Collection')
            ? Str::replaceLast('Collection', '', $this->argument('name'))
            : $this->argument('name');

        $this->input->setArgument('name', "{$baseName}Collection");

        parent::handle();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the resource already exists'],
            ['collection', 'c', InputOption::VALUE_NONE, 'Create a resource collection'],
            ['all', 'a', InputOption::VALUE_NONE, 'Generate both: resource and its collection'],
        ];
    }
}
