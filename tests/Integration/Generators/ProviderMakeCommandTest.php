<?php

namespace Illuminate\Tests\Integration\Generators;

class ProviderMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Providers/FooServiceProvider.php',
    ];

    public function testItCanGenerateServiceProviderFile()
    {
        $this->artisan('make:provider', ['name' => 'FooServiceProvider'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Providers;',
            'use Illuminate\Support\ServiceProvider;',
            'class FooServiceProvider extends ServiceProvider',
            'public function register()',
            'public function boot()',
        ], 'app/Providers/FooServiceProvider.php');
    }

    public function testItCanGenerateDeferredServiceProviderFile()
    {
        $this->artisan('make:provider', ['name' => 'FooServiceProvider', '--deferred' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Providers;',
            'use Illuminate\Support\ServiceProvider;',
            'class FooServiceProvider extends ServiceProvider implements DeferrableProvider',
            'public function register()',
            'public function provides()',
        ], 'app/Providers/FooServiceProvider.php');
    }
}
