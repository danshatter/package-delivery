<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class JsonMax implements Rule
{

    private $max;

    /**
     * Create an instance
     */
    public function __construct($max)
    {
        $this->max = $max;
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
        $decoded = json_decode($value, true);

        return count($decoded) <= $this->max;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must not be more than '.$this->max;
    }

}
