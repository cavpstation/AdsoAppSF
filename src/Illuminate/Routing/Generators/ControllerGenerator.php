<?php namespace Illuminate\Routing\Generators; use Illuminate\Filesystem\Filesystem; class ControllerGenerator { protected $files; protected $defaults = array( 'index', 'create', 'store', 'show', 'edit', 'update', 'destroy' ); public function __construct(Filesystem $files) { $this->files = $files; } public function make($controller, $path, array $options = array()) { $stub = $this->addMethods($this->getController($controller), $options); $this->writeFile($stub, $controller, $path); return false; } protected function writeFile($stub, $controller, $path) { if (str_contains($controller, '\\')) { $this->makeDirectory($controller, $path); } $controller = str_replace('\\', DIRECTORY_SEPARATOR, $controller); if ( ! $this->files->exists($fullPath = $path."/{$controller}.php")) { return $this->files->put($fullPath, $stub); } } protected function makeDirectory($controller, $path) { $directory = $this->getDirectory($controller); if ( ! $this->files->isDirectory($full = $path.'/'.$directory)) { $this->files->makeDirectory($full, 0777, true); } } protected function getDirectory($controller) { return implode('/', array_slice(explode('\\', $controller), 0, -1)); } protected function getController($controller) { $stub = $this->files->get(__DIR__.'/stubs/controller.stub'); $segments = explode('\\', $controller); $stub = $this->replaceNamespace($segments, $stub); return str_replace('{{class}}', last($segments), $stub); } protected function replaceNamespace(array $segments, $stub) { if (count($segments) > 1) { $namespace = implode('\\', array_slice($segments, 0, -1)); return str_replace('{{namespace}}', ' namespace '.$namespace.';', $stub); } else { return str_replace('{{namespace}}', '', $stub); } } protected function addMethods($stub, array $options) { $stubs = $this->getMethodStubs($options); $methods = implode(PHP_EOL.PHP_EOL, $stubs); return str_replace('{{methods}}', $methods, $stub); } protected function getMethodStubs($options) { $stubs = array(); foreach ($this->getMethods($options) as $method) { $stubs[] = $this->files->get(__DIR__."/stubs/{$method}.stub"); } return $stubs; } protected function getMethods($options) { if (isset($options['only']) && count($options['only']) > 0) { return $options['only']; } elseif (isset($options['except']) && count($options['except']) > 0) { return array_diff($this->defaults, $options['except']); } return $this->defaults; } }
