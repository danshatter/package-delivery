<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\{Rule, DataAwareRule};
use App\Models\User;
use App\Services\Phone\Nigeria;

class DifferentPhone implements Rule, DataAwareRule
{
    
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
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
        return app()->make(Nigeria::class)->convert($value) !== app()->make(Nigeria::class)->convert($this->data['sender_phone']);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be different from sender phone';
    }

}
