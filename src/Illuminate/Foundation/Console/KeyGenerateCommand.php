<?php namespace Illuminate\Foundation\Console; use Illuminate\Support\Str; use Illuminate\Console\Command; use Illuminate\Filesystem\Filesystem; class KeyGenerateCommand extends Command { protected $name = 'key:generate'; protected $description = "Set the application key"; public function __construct(Filesystem $files) { parent::__construct(); $this->files = $files; } public function fire() { list($path, $contents) = $this->getKeyFile(); $key = $this->getRandomKey(); $contents = str_replace($this->laravel['config']['app.key'], $key, $contents); $this->files->put($path, $contents); $this->laravel['config']['app.key'] = $key; $this->info("Application key [$key] set successfully."); } protected function getKeyFile() { $env = $this->option('env') ? $this->option('env').'/' : ''; $contents = $this->files->get($path = $this->laravel['path']."/config/{$env}app.php"); return array($path, $contents); } protected function getRandomKey() { return Str::random(32); } }
