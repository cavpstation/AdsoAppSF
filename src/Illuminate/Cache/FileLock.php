<?php

namespace Illuminate\Cache;

use Illuminate\Support\Carbon;

class FileLock extends Lock
{
    /**
     * The parent array file store.
     *
     * @var \Illuminate\Cache\FileStore
     */
    protected $store;

    /**
     * The lock file path.
     *
     * @var string
     */
    protected $path;

    /**
     * The extension that will be used for lock files.
     *
     * @var string
     */
    protected $extension = '.lock';

    /**
     * Create a new lock instance.
     *
     * @param  \Illuminate\Cache\FileStore  $store
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct($store, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->store = $store;
        $this->path = $this->store->getDirectory() . '/' . $this->name . $this->extension;
    }

    /**
     * Get file content if exists.
     *
     * @return string|null
     */
    private function getLockFileContent()
    {
        if ($this->exists()) {
            return json_decode($this->store->getFilesystem()->get($this->path));
        }

        return null;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        $now = Carbon::now();
        $content = $this->getLockFileContent();
        $expiration = is_null($content) || is_null($content->expiresAt) ? $now->copy() : Carbon::parse($content->expiresAt);

        if ($expiration->isFuture()) {
            return false;
        }

        $this->store->getFilesystem()->put($this->path, json_encode([
            'owner' => $this->owner,
            'expiresAt' => $this->seconds === 0 ? null : $now->copy()->addSeconds($this->seconds),
        ]), true);

        return true;
    }

    /**
     * Determine if the current lock exists.
     *
     * @return bool
     */
    protected function exists()
    {
        return $this->store->getFilesystem()->exists($this->path);
    }

    /**
     * Release the lock.
     *
     * @return bool
     */
    public function release()
    {
        if (! $this->exists()) {
            return false;
        }

        if (! $this->isOwnedByCurrentProcess()) {
            return false;
        }

        $this->forceRelease();

        return true;
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return string
     */
    protected function getCurrentOwner()
    {
        $content = $this->getLockFileContent();

        return $content->owner;
    }

    /**
     * Releases this lock in disregard of ownership.
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->store->getFilesystem()->delete($this->path);
    }
}
