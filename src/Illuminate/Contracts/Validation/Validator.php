<?php

namespace Illuminate\Contracts\Validation;

use Illuminate\Contracts\Support\MessageProvider;

interface Validator extends MessageProvider
{
    /**
     * Determine if the data fails the validation rules.
     *
     * @return bool
     */
    public function fails();

    /**
     * Get the failed validation rules.
     *
     * @return array
     */
    public function failed();

    /**
     * Add conditions to a given field based on a Closure.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @param  callable  $callback
     * @return $this
     */
    public function sometimes($attribute, $rules, callable $callback);

    /**
     * After an after validation callback.
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function after($callback);

    /**
     * Get all of the validation error messages.
     *
     * @return array
     */
    public function errors();

    /**
     * Validate a given attribute against a rule.
     *
     * @param  $attribute
     * @param  $rule
     * @return void
     */
    public function validateAttribute($attribute, $rule);
}
