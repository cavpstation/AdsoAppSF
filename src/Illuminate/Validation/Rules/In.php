<?php

namespace Illuminate\Validation\Rules;

class In extends Rule
{
    /**
     * The name of the rule.
     */
    protected $rule = 'in';

    /**
     * The accepted values.
     *
     * @var array
     */
    protected $values;

    /**
     * Create a new in rule instance.
     *
     * @param  array  $values
     * @return void
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * The string representation of the rule.
     *
     * @return string
     */
    public function toString()
    {
        return $this->rule.':'.implode(',', $this->values);
    }
}
