<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Str;

trait AuthorizesRequests
{
    /**
     * Resource ability map.
     *
     * @var array
     */
    protected $resourceAbilities = [
        'index' => 'viewAny',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'destroy' => 'delete',
    ];

    /**
     * Excluded resource abilities.
     *
     * @var array
     */
    protected $excludeResourceAbilities = [];

    /**
     * Authorize a given action for the current user.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability, $arguments);

        return app(Gate::class)->authorize($ability, $arguments);
    }

    /**
     * Authorize a given action for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeForUser($user, $ability, $arguments = [])
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability, $arguments);

        return app(Gate::class)->forUser($user)->authorize($ability, $arguments);
    }

    /**
     * Guesses the ability's name if it wasn't provided.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return array
     */
    protected function parseAbilityAndArguments($ability, $arguments)
    {
        if (is_string($ability) && strpos($ability, '\\') === false) {
            return [$ability, $arguments];
        }

        $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'];

        return [$this->normalizeGuessedAbilityName($method), $ability];
    }

    /**
     * Normalize the ability name that has been guessed from the method name.
     *
     * @param  string  $ability
     * @return string
     */
    protected function normalizeGuessedAbilityName($ability)
    {
        $map = $this->resourceAbilityMap();

        return $map[$ability] ?? $ability;
    }

    /**
     * Authorize a resource action based on the incoming request.
     *
     * @param  string  $model
     * @param  string|null  $parameter
     * @param  array  $options
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     */
    public function authorizeResource($model, $parameter = null, array $options = [], $request = null)
    {
        $parameter = $parameter ?: Str::snake(class_basename($model));

        $middleware = [];

        foreach ($this->resourceAbilityMap() as $method => $ability) {
            $modelName = in_array($method, $this->resourceMethodsWithoutModels()) ? $model : $parameter;

            $middleware["can:{$ability},{$modelName}"][] = $method;
        }

        foreach ($middleware as $middlewareName => $methods) {
            $this->middleware($middlewareName, $options)->only($methods);
        }
    }

    /**
     * Set resource abilities.
     *
     * @param array $resourceAbilities
     */
    public function setResourceAbilities(array $resourceAbilities)
    {
        $this->resourceAbilities = $resourceAbilities;

        return $this;
    }

    /**
     * Use only the given abilities.
     *
     * @param string|array $abilities
     */
    public function onlyResourceAbilities($abilities)
    {
        $abilities = is_array($abilities) ? $abilities : func_get_args();

        $this->resourceAbilities = array_filter($this->resourceAbilities, function ($ability) use ($abilities) {
            return in_array($ability, $abilities);
        });

        return $this;
    }

    /**
     * Exclude resource abilities.
     *
     * @param  array $abilities
     * @return this
     */
    public function excludeResourceAbilities($abilities)
    {
        $this->excludeResourceAbilities = is_array($abilities) ? $abilities : func_get_args();

        return $this;
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return array_filter($this->resourceAbilities, function ($ability) {
            return ! in_array($ability, $this->excludeResourceAbilities);
        });
    }

    /**
     * Get the list of resource methods which do not have model parameters.
     *
     * @return array
     */
    protected function resourceMethodsWithoutModels()
    {
        return ['index', 'create', 'store'];
    }
}
