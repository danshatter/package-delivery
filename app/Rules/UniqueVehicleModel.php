<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\{Rule, DataAwareRule};
use App\Models\VehicleModel;

class UniqueVehicleModel implements Rule, DataAwareRule
{
    
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data;
    private $vehicleModel;

    /**
     * Create an instance
     */
    public function __construct($vehicleModel = null)
    {
        $this->vehicleModel = $vehicleModel;
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
        // If the name vehicle model is null or if a different vehicle model is being assigned
        if (is_null($this->vehicleModel) || $this->data['brand'] != $this->vehicleModel->vehicle_brand_id) {
            return VehicleModel::where('vehicle_brand_id', $this->data['brand'])
                            ->where('name', $value)
                            ->doesntExist();
        }

        // if only the name is being changed
        return VehicleModel::where('vehicle_brand_id', $this->data['brand'])
                        ->where('name', $value)
                        ->where('name', '!=', $this->vehicleModel->name)
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
