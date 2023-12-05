<?php

namespace App\Http\Controllers;

use App\Models\{VehicleBrand, Vehicle};
use App\Rules\UniqueVehicleBrand;

class VehicleBrandController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get all vehicle brands
     */
    public function index()
    {
        $vehicleBrands = VehicleBrand::all();

        return $this->sendSuccess('Request successful', $vehicleBrands);
    }

    /**
     * Get a vehicle brand
     */
    public function show($vehicleBrandId)
    {
        $vehicleBrand = VehicleBrand::find($vehicleBrandId);

        if (is_null($vehicleBrand)) {
            return $this->sendErrorMessage('Vehicle brand not found', 404);
        }

        return $this->sendSuccess('Request successful', $vehicleBrand);
    }

    /**
     * Get the models of a vehicle brand
     */
    public function showModels($vehicleBrandId)
    {
        $vehicleBrand = VehicleBrand::with(['vehicleModels'])->find($vehicleBrandId);

        if (is_null($vehicleBrand)) {
            return $this->sendErrorMessage('Vehicle brand not found', 404);
        }

        return $this->sendSuccess('Request successful', $vehicleBrand->vehicleModels);
    }

    /**
     * Create a vehicle brand
     */
    public function store()
    {
        $validator = validator()->make(request()->all(), [
            'type' => ['required', 'exists:vehicles,id'],
            'name' => ['required', new UniqueVehicleBrand]
        ], [
            'type.exists' => 'The vehicle :attribute does not exist'
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the vehicle
        $vehicle = Vehicle::withTrashed()->find($type);

        // Create the vehicle brand
        $vehicleBrand = $vehicle->vehicleBrands()->create(compact('name'));

        return $this->sendSuccess('Vehicle brand created successfully', $vehicleBrand, 201);
    }

    /**
     * Update a vehicle brand
     */
    public function update($vehicleBrandId)
    {
        $vehicleBrand = VehicleBrand::find($vehicleBrandId);

        if (is_null($vehicleBrand)) {
            return $this->sendSuccess('Vehicle brand not found', 404);
        }

        $validator = validator()->make(request()->all(), [
            'type' => ['required', 'exists:vehicles,id'],
            'name' => ['required', new UniqueVehicleBrand($vehicleBrand)]
        ], [
            'type.exists' => 'The vehicle :attribute does not exist'
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the vehicle brand
        $vehicleBrand->forceFill([
            'vehicle_id' => $type,
            'name' => $name
        ])->save();

        return $this->sendSuccess('Vehicle brand updated successfully', $vehicleBrand);
    }

    /**
     * Delete a vehicle brand
     */
    public function destroy($vehicleBrandId)
    {
        $vehicleBrand = VehicleBrand::find($vehicleBrandId);

        if (is_null($vehicleBrand)) {
            return $this->sendErrorMessage('Vehicle brand not found', 404);
        }

        $vehicleBrand->delete();

        return $this->sendSuccess('Vehicle brand deleted successfully');
    }

}
