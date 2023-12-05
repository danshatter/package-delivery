<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\User;
use App\Services\Settings\Application;

class ValidDriver implements Rule
{
    
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // The driver should have all the requirements to test validity
        return User::validDrivers()
                    ->where('id', $value)
                    ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        return 'This driver is not a valid driver at '.config('app.name');
    }

}
