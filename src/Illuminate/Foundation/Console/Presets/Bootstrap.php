<?php

namespace Illuminate\Foundation\Console\Presets;

use Illuminate\Support\Arr;
use Illuminate\Filesystem\Filesystem;

class Bootstrap extends Preset
{
    /**
     * Install the preset.
     *
     * @return void
     */
    public static function install()
    {
        static::updatePackages();
        static::updateSass();
        static::updateBootstrapping();
        static::removeNodeModules();
    }

    /**
     * Update the given package array.
     *
     * @param  array  $packages
     * @return array
     */
    protected static function updatePackageArray(array $packages)
    {
        return ['bootstrap-sass' => '^3.3.7', 'jquery' => '^3.1.1'] + Arr::except($packages, [
            'uikit'
        ]);
    }

    /**
     * Update the Sass files for the application.
     *
     * @return void
     */
    protected static function updateSass()
    {
        copy(__DIR__.'/bootstrap-stubs/_variables.scss', resource_path('assets/sass/_variables.scss'));
        copy(__DIR__.'/bootstrap-stubs/app.scss', resource_path('assets/sass/app.scss'));
    }

    /**
     * Update the bootstrapping files.
     *
     * @return void
     */
    protected static function updateBootstrapping()
    {
        tap(new Filesystem, function ($filesystem) {
            $bootstrapJs = str_replace(
                "window.UIkit = require('uikit');",
                "require('bootstrap-sass');",
                $filesystem->get(resource_path('assets/js/bootstrap.js'))
            );

            $filesystem->put(resource_path('assets/js/bootstrap.js'), $bootstrapJs);
        });
    }
}
