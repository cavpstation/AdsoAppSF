<?php

namespace Illuminate\Contracts\Filesystem;

interface Cloud extends Filesystem
{
    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @param  array  $options
     * @return string
     */
    public function url($path, array $options = []);
}
