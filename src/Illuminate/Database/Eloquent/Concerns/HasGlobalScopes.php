<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Arr;
use InvalidArgumentException;

trait HasGlobalScopes
{
    /**
     * Register a new global scope on the model.
     *
     * @param  \Illuminate\Database\Eloquent\Scope|\Closure|string  $scope
     * @param  \Illuminate\Database\Eloquent\Scope|\Closure|null  $implementation
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function addGlobalScope($scope, $implementation = null)
    {
        if (is_string($scope) && ($implementation instanceof Closure || $implementation instanceof Scope)) {
            return static::$globalScopes[static::class][$scope] = $implementation;
        } elseif ($scope instanceof Closure) {
            return static::$globalScopes[static::class][spl_object_hash($scope)] = $scope;
        } elseif ($scope instanceof Scope) {
            return static::$globalScopes[static::class][get_class($scope)] = $scope;
        }

        throw new InvalidArgumentException('Global scope must be an instance of Closure or Scope.');
    }

    /**
     * Determine if a model has a global scope.
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return bool
     */
    public static function hasGlobalScope($scope)
    {
        return ! is_null(static::getGlobalScope($scope));
    }

    /**
     * Get a global scope registered with the model.
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return \Illuminate\Database\Eloquent\Scope|\Closure|null
     */
    public static function getGlobalScope($scope)
    {
        if (is_string($scope)) {
            return Arr::get(static::$globalScopes, static::class.'.'.$scope);
        }

        return Arr::get(
            static::$globalScopes, static::class.'.'.get_class($scope)
        );
    }

    /**
     * Get all the global scopes registered.
     *
     * @return array
     */
    public static function getAllGlobalScopes()
    {
        return static::$globalScopes;
    }

    /**
     * Set all the global scopes.
     *
     * @params  array  $scopes
     * @return void
     */
    public static function setAllGlobalScopes($scopes)
    {
        static::$globalScopes = $scopes;
    }

    /**
     * Get the global scopes for this class instance.
     *
     * @return array
     */
    public function getGlobalScopes()
    {
        return Arr::get(static::$globalScopes, static::class, []);
    }
}
