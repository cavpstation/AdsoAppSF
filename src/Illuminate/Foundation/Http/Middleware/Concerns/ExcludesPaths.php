<?php

namespace Illuminate\Foundation\Http\Middleware\Concerns;

trait ExcludesPaths
{
    /**
     * The URIs that should be excluded.
     *
     * @var array<int, string>
     */
    protected $except = [];

    /**
     * Get the URIs that should be excluded.
     *
     * @return array
     */
    public function getExcludedPaths()
    {
        return $this->except;
    }

    /**
     * Determine if the request has a URI that should be exclued.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->getExcludedPaths() as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
