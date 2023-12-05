<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Vehicle;

class VehicleExists implements Rule
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
        // Get the vehicle
        $vehicle = Vehicle::find($value);

        // Check if the vehicle is in the database
        if (is_null($vehicle)) {
            return false;
        }

        // Vehicle exists, now check if it has been soft deleted
        return !$vehicle->trashed();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This vehicle does not exist';
    }

}
