<?php

namespace Illuminate\Support\Traits;

use Closure;
use BadMethodCallException;

trait Macroable
{
    /**
     * The registered string macros.
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * Register a custom macro.
     *
     * @param  string    $name
     * @param  callable  $macro
     * @return void
     */
    public static function macro($name, callable $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Checks if macro is registered.
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasMacro($name)
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        return static::resolveMacroCall($method, $parameters, Closure::bind(static::$macros[$method], null, static::class));
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        return static::resolveMacroCall($method, $parameters, static::$macros[$method]->bindTo($this, static::class));
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @param  Closure $bind
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    protected function resolveMacroCall($method, $parameters, $bind)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        if (static::$macros[$method] instanceof Closure) {
            return call_user_func_array($bind, $parameters);
        }

        return call_user_func_array(static::$macros[$method], $parameters);
    }
}
