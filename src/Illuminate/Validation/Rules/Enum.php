<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use TypeError;

class Enum implements Rule, ValidatorAwareRule
{
    /**
     * The type of the enum.
     *
     * @var string
     */
    protected $type;

    /**
     * The current validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * The cases that should be considered valid.
     *
     * @var array
     */
    private array $only = [];

    /**
     * The cases that should be considered invalid.
     *
     * @var array
     */
    private array $except = [];

    /**
     * Create a new rule instance.
     *
     * @param  string  $type
     * @return void
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($value instanceof $this->type) {
            return $this->isDesirable($value);
        }

        if (is_null($value) || ! enum_exists($this->type) || ! method_exists($this->type, 'tryFrom')) {
            return false;
        }

        try {
            $value = $this->type::tryFrom($value);

            return ! is_null($value) && $this->isDesirable($value);
        } catch (TypeError) {
            return false;
        }
    }

    /**
     * Specify the cases that should be considered valid.
     *
     * @param  \UnitEnum[]|\UnitEnum  $values
     */
    public function only($values): static
    {
        $this->only = Arr::wrap($values);

        return $this;
    }

    /**
     * Specify the cases that should be considered invalid.
     *
     * @param  \UnitEnum[]|\UnitEnum  $values
     */
    public function except($values): static
    {
        $this->except = Arr::wrap($values);

        return $this;
    }

    /**
     * Determine if the given case is a valid case based on the only / except values.
     *
     * @param  mixed  $value
     * @return bool
     */
    private function isDesirable(mixed $value): bool
    {
        return match (true) {
            ! empty($this->only) => in_array(needle: $value, haystack: $this->only, strict: true),
            ! empty($this->except) => ! in_array(needle: $value, haystack: $this->except, strict: true),
            default => true,
        };
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = $this->validator->getTranslator()->get('validation.enum');

        return $message === 'validation.enum'
            ? ['The selected :attribute is invalid.']
            : $message;
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
