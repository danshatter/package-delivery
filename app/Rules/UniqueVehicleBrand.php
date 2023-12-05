<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\{Rule, DataAwareRule};
use App\Models\VehicleBrand;

class UniqueVehicleBrand implements Rule, DataAwareRule
{
    
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data;
    private $vehicleBrand;

    /**
     * Create an instance
     */
    public function __construct($vehicleBrand = null)
    {
        $this->vehicleBrand = $vehicleBrand;
    }

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
        // If the name vehicle brand is null or if a different vehicle brand is being assigned
        if (is_null($this->vehicleBrand) || $this->data['type'] != $this->vehicleBrand->vehicle_id) {
            return VehicleBrand::where('vehicle_id', $this->data['type'])
                            ->where('name', $value)
                            ->doesntExist();
        }

        // if only the name is being changed
        return VehicleBrand::where('vehicle_id', $this->data['type'])
                        ->where('name', $value)
                        ->where('name', '!=', $this->vehicleBrand->name)
                        ->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute has already been added';
    }

}
