<?php

namespace Illuminate\Foundation\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\PhpExecutableFinder;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'install:broadcasting')]
class BroadcastingInstallCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:broadcasting
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}
                    {--force : Overwrite any existing broadcasting routes file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a broadcasting channel routes file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('config:publish', ['name' => 'broadcasting']);

        // Install channel routes file...
        if (! file_exists($broadcastingRoutesPath = $this->laravel->basePath('routes/channels.php')) || $this->option('force')) {
            $this->components->info("Published 'channels' route file.");

            copy(__DIR__.'/stubs/broadcasting-routes.stub', $broadcastingRoutesPath);
        }

        $this->uncommentChannelsRoutesFile();
        $this->updateBroadcastingConfiguration();
        $this->enableBroadcastServiceProvider();

        // Install bootstrapping...
        if (! file_exists($echoScriptPath = $this->laravel->resourcePath('js/echo.js'))) {
            copy(__DIR__.'/stubs/echo-js.stub', $echoScriptPath);
        }

        if (file_exists($bootstrapScriptPath = $this->laravel->resourcePath('js/bootstrap.js'))) {
            $bootstrapScript = file_get_contents(
                $bootstrapScriptPath
            );

            if (! str_contains($bootstrapScript, './echo')) {
                file_put_contents(
                    $bootstrapScriptPath,
                    trim($bootstrapScript.PHP_EOL.file_get_contents(__DIR__.'/stubs/echo-bootstrap-js.stub')).PHP_EOL,
                );
            }
        }

        $this->installReverb();

        $this->installNodeDependencies();
    }

    /**
     * Uncomment the "channels" routes file in the application bootstrap file.
     *
     * @return void
     */
    protected function uncommentChannelsRoutesFile()
    {
        $appBootstrapPath = $this->laravel->bootstrapPath('app.php');

        $content = file_get_contents($appBootstrapPath);

        if (str_contains($content, '// channels: ')) {
            (new Filesystem)->replaceInFile(
                '// channels: ',
                'channels: ',
                $appBootstrapPath,
            );
        } elseif (str_contains($content, 'channels: ')) {
            return;
        } elseif (str_contains($content, 'commands: __DIR__.\'/../routes/console.php\',')) {
            (new Filesystem)->replaceInFile(
                'commands: __DIR__.\'/../routes/console.php\',',
                'commands: __DIR__.\'/../routes/console.php\','.PHP_EOL.'        channels: __DIR__.\'/../routes/channels.php\',',
                $appBootstrapPath,
            );
        }
    }

    /**
     * Update the broadcasting.php configuration file.
     *
     * @return void
     */
    protected function updateBroadcastingConfiguration()
    {
        if ($this->laravel->config->has('broadcasting.connections.reverb')) {
            return;
        }

        (new Filesystem)->replaceInFile(
            "'connections' => [\n",
            <<<'CONFIG'
            'connections' => [

                    'reverb' => [
                        'driver' => 'reverb',
                        'key' => env('REVERB_APP_KEY'),
                        'secret' => env('REVERB_APP_SECRET'),
                        'app_id' => env('REVERB_APP_ID'),
                        'options' => [
                            'host' => env('REVERB_HOST'),
                            'port' => env('REVERB_PORT', 443),
                            'scheme' => env('REVERB_SCHEME', 'https'),
                            'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
                        ],
                        'client_options' => [
                            // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
                        ],
                    ],

            CONFIG,
            app()->configPath('broadcasting.php')
        );
    }

    /**
     * Uncomment the "BroadcastServiceProvider" in the application configuration.
     *
     * @return void
     */
    protected function enableBroadcastServiceProvider()
    {
        $config = ($filesystem = new Filesystem)->get(app()->configPath('app.php'));

        if (str_contains($config, '// App\Providers\BroadcastServiceProvider::class')) {
            $filesystem->replaceInFile(
                '// App\Providers\BroadcastServiceProvider::class',
                'App\Providers\BroadcastServiceProvider::class',
                app()->configPath('app.php'),
            );
        }
    }

    /**
     * Install Laravel Reverb into the application if desired.
     *
     * @return void
     */
    protected function installReverb()
    {
        if (InstalledVersions::isInstalled('laravel/reverb')) {
            return;
        }

        $install = confirm('Would you like to install Laravel Reverb?', default: true);

        if (! $install) {
            return;
        }

        $this->requireComposerPackages($this->option('composer'), [
            'laravel/reverb:@beta',
        ]);

        $php = (new PhpExecutableFinder())->find(false) ?: 'php';

        Process::run([
            $php,
            defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan',
            'reverb:install',
        ]);

        $this->components->info('Reverb installed successfully.');
    }

    /**
     * Install and build Node dependencies.
     *
     * @return void
     */
    protected function installNodeDependencies()
    {
        if (! confirm('Would you like to install and build the Node dependencies required for broadcasting?', default: true)) {
            return;
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $commands = [
                'pnpm add --save-dev laravel-echo pusher-js',
                'pnpm run build',
            ];
        } elseif (file_exists(base_path('yarn.lock'))) {
            $commands = [
                'yarn add --dev laravel-echo pusher-js',
                'yarn run build',
            ];
        } elseif (file_exists(base_path('bun.lockb'))) {
            $commands = [
                'bun add --dev laravel-echo pusher-js',
                'bun run build',
            ];
        } else {
            $commands = [
                'npm install --save-dev laravel-echo pusher-js',
                'npm run build',
            ];
        }

        $command = Process::command(implode(' && ', $commands))
            ->path(base_path());

        if (! windows_os()) {
            $command->tty(true);
        }

        if ($command->run()->failed()) {
            $this->components->warn("Node dependency installation failed. Please run the following commands manually: \n\n".implode(' && ', $commands));
        } else {
            $this->components->info('Node dependencies installed successfully.');
        }
    }
}
