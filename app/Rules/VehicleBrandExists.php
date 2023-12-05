<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\{Rule, DataAwareRule};
use App\Models\VehicleBrand;

class VehicleBrandExists implements Rule, DataAwareRule
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
        return VehicleBrand::where('vehicle_id', $this->data['vehicle_type'])
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
        return 'The :attribute does not exist';
    }

}
