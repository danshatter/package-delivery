<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\User;
use App\Services\Phone\Nigeria;

class PhoneUnique implements Rule
{
    
    public function __construct($user = null)
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
        // For the case of account creation
        if (is_null($this->user)) {
            return User::where('phone', app()->make(Nigeria::class)->convert($value))
                        ->doesntExist();
        }

        // For the case of updating an account
        return User::where('phone', app()->make(Nigeria::class)->convert($value))
                    ->where('phone', '!=', $this->user->phone)
                    ->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute has already been taken';
    }

}
