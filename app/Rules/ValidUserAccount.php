<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidUserAccount implements Rule
{

    private $user;
    
    /**
     * 
     */
    public function __construct($user)
    {
        $this->user = $user;
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
        return $this->user->accounts()->where('id', $value)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This account does not exist';
    }

}
