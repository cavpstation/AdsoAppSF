<?php

namespace Illuminate\Database\Console\Factories;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class FactoryMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:factory';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'make:factory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Factory';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/factory.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $factory = class_basename(Str::ucfirst(str_replace('Factory', '', $name)));

        $namespaceModel = $this->option('model')
            ? $this->qualifyModel($this->option('model'))
            : $this->guessModelName($name);



        $model = class_basename($namespaceModel);
        $namespace = $this->getNamespace($name);

        $replace = [
            '{{ factoryNamespace }}' => $namespace,
            'NamespacedDummyModel' => $namespaceModel,
            '{{ namespacedModel }}' => $namespaceModel,
            '{{namespacedModel}}' => $namespaceModel,
            'DummyModel' => $model,
            '{{ model }}' => $model,
            '{{model}}' => $model,
            '{{ factory }}' => $factory,
            '{{factory}}' => $factory,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = (string)Str::of($name)->replaceFirst($this->rootNamespace(), '')->finish('Factory');

        return $this->laravel->databasePath() . '/factories/' . str_replace('\\', '/', $name) . '.php';
    }


    /**
     * Guess the model name from the Factory name or return a default model name.
     *
     * @param string $name
     * @return string
     */
    protected function guessModelName($name)
    {
        if (Str::endsWith($name, 'Factory')) {
            $name = substr($name, 0, -7);
        }

        $modelName = $this->qualifyModel(Arr::last(explode('\\', $name)));

        if (class_exists($modelName)) {
            return $modelName;
        }

        if (is_dir(app_path('Models/'))) {
            return $this->laravel->getNamespace() . 'Models\Model';
        }

        return $this->laravel->getNamespace() . 'Model';
    }

    /**
     * Qualify the given model class base name.
     *
     * @param string $model
     * @return string
     */
    protected function qualifyModel(string $model)
    {
        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->laravel->getNamespace();

        if (Str::startsWith($model, $rootNamespace . 'Models\\')) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace . 'Models\\' . $model
            : $rootNamespace . $model;
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return 'Database\Factories\\';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The name of the model'],
        ];
    }
}
