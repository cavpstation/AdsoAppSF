<?php

namespace Illuminate\Foundation;

use Exception;
use Illuminate\Filesystem\Filesystem;

class PackageManifest
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    public $files;

    /**
     * The base path.
     *
     * @var string
     */
    public $basePath;

    /**
     * The vendor path.
     *
     * @var string
     */
    public $vendorPath;

    /**
     * The manifest path.
     *
     * @var string|null
     */
    public $manifestPath;

    /**
     * The loaded manifest array.
     *
     * @var array
     */
    public $manifest;

    /**
     * The installed packages.
     *
     * @var array
     */
    public $installedPackages;

    /**
     * The auto discovered packages.
     *
     * @var array
     */
    public $packages;

    /**
     * The recorded package names.
     *
     * @var array
     */
    public $recordedPackages = [];

    /**
     * All auto discovered package names ordered by their dependencies.
     *
     * @var array
     */
    public $sortingMask = [];

    /**
     * Create a new package manifest instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $basePath
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, $basePath, $manifestPath)
    {
        $this->files = $files;
        $this->basePath = $basePath;
        $this->manifestPath = $manifestPath;
        $this->vendorPath = $basePath.'/vendor';
    }

    /**
     * Get all of the service provider class names for all packages.
     *
     * @return array
     */
    public function providers()
    {
        return collect($this->getManifest())->flatMap(function ($configuration) {
            return (array) ($configuration['providers'] ?? []);
        })->filter()->all();
    }

    /**
     * Get all of the aliases for all packages.
     *
     * @return array
     */
    public function aliases()
    {
        return collect($this->getManifest())->flatMap(function ($configuration) {
            return (array) ($configuration['aliases'] ?? []);
        })->filter()->all();
    }

    /**
     * Get the current package manifest.
     *
     * @return array
     */
    protected function getManifest()
    {
        if (! is_null($this->manifest)) {
            return $this->manifest;
        }

        if (! file_exists($this->manifestPath)) {
            $this->build();
        }

        $this->files->get($this->manifestPath);

        return $this->manifest = file_exists($this->manifestPath) ?
            $this->files->getRequire($this->manifestPath) : [];
    }

    /**
     * Build the manifest and write it to disk.
     *
     * @return void
     */
    public function build()
    {
        if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
            $this->installedPackages = json_decode($this->files->get($path), true);
        }

        $ignoreAll = in_array('*', $ignore = $this->packagesToIgnore());

        $this->packages = collect($this->installedPackages)->mapWithKeys(function ($package) {
            return [$this->format($package['name']) => $package['extra']['laravel'] ?? []];
        })->each(function ($configuration) use (&$ignore) {
            $ignore = array_merge($ignore, $configuration['dont-discover'] ?? []);
        })->reject(function ($configuration, $package) use ($ignore, $ignoreAll) {
            return $ignoreAll || in_array($package, $ignore);
        })->filter()->all();

        if ($this->shouldSortPackages()) {
            $this->sortPackages();
        }

        $this->write($this->packages);
    }

    /**
     * Format the given package name.
     *
     * @param  string  $package
     * @return string
     */
    protected function format($package)
    {
        return str_replace($this->vendorPath.'/', '', $package);
    }

    /**
     * Determine if packages should be sorted.
     *
     * @return bool
     */
    protected function shouldSortPackages()
    {
        if (! file_exists($this->basePath.'/composer.json') || ! count($this->packages)) {
            return false;
        }

        return json_decode(file_get_contents(
            $this->basePath.'/composer.json'
        ), true)['extra']['laravel']['sort-providers'] ?? false;
    }

    /**
     * Sort the auto discovered packages in order of their dependencies.
     *
     * @return void
     */
    protected function sortPackages()
    {
        foreach (array_keys($this->packages) as $packageName) {
            $this->recordPackagesByDependency($packageName);
        }

        $this->packages = array_replace(array_flip($this->sortingMask), $this->packages);
    }

    /**
     * Record a deep list of all auto discovered package names in order of their dependencies.
     *
     * @param  string  $path
     * @return void
     */
    protected function recordPackagesByDependency($packageName)
    {
        if (in_array($packageName, $this->recordedPackages)) {
            return;
        }

        $this->recordedPackages[] = $packageName;

        $package = collect($this->installedPackages)->first(function ($value) use ($packageName) {
            return $value['name'] == $packageName;
        });

        if (is_null($package)) {
            return;
        }

        $packageRequires = array_merge(
            array_keys($package['require'] ?? []),
            array_keys($package['require-dev'] ?? [])
        );

        foreach ($packageRequires as $required) {
            $this->recordPackagesByDependency($required);
        }

        if (array_key_exists($packageName, $this->packages)) {
            $this->sortingMask[] = $packageName;
        }
    }

    /**
     * Get all of the package names that should be ignored.
     *
     * @return array
     */
    protected function packagesToIgnore()
    {
        if (! file_exists($this->basePath.'/composer.json')) {
            return [];
        }

        return json_decode(file_get_contents(
            $this->basePath.'/composer.json'
        ), true)['extra']['laravel']['dont-discover'] ?? [];
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  array  $manifest
     * @return void
     *
     * @throws \Exception
     */
    protected function write(array $manifest)
    {
        if (! is_writable(dirname($this->manifestPath))) {
            throw new Exception('The '.dirname($this->manifestPath).' directory must be present and writable.');
        }

        $this->files->replace(
            $this->manifestPath, '<?php return '.var_export($manifest, true).';'
        );
    }
}
