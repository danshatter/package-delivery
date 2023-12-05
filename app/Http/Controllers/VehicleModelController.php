<?php

namespace App\Http\Controllers;

use App\Models\{VehicleModel, VehicleBrand};
use App\Rules\{UniqueVehicleModel};

class VehicleModelController extends Controller
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
     * Get all vehicle models
     */
    public function index()
    {
        $vehicleModels = VehicleModel::all();

        return $this->sendSuccess('Request successful', $vehicleModels);
    }

    /**
     * Get a vehicle model
     */
    public function show($vehicleModelId)
    {
        $vehicleModel = VehicleModel::find($vehicleModelId);

        if (is_null($vehicleModel)) {
            return $this->sendErrorMessage('Vehicle model not found', 404);
        }

        return $this->sendSuccess('Request successful', $vehicleModel);
    }

    /**
     * Create a vehicle model
     */
    public function store()
    {
        $validator = validator()->make(request()->all(), [
            'brand' => ['required', 'exists:vehicle_brands,id'],
            'name' => ['required', new UniqueVehicleModel]
        ], [
            'brand.exists' => 'The vehicle :attribute does not exist'
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the vehicle brand
        $vehicleBrand = VehicleBrand::find($brand);

        // Create the vehicle model
        $vehicleModel = $vehicleBrand->vehicleModels()->create(compact('name'));

        return $this->sendSuccess('Vehicle model created successfully', $vehicleModel, 201);
    }

    /**
     * Update a vehicle model
     */
    public function update($vehicleModelId)
    {
        $vehicleModel = VehicleModel::find($vehicleModelId);

        if (is_null($vehicleModel)) {
            return $this->sendErrorMessage('Vehicle model not found', 404);
        }

        $validator = validator()->make(request()->all(), [
            'brand' => ['required', 'exists:vehicle_brands,id'],
            'name' => ['required', new UniqueVehicleModel($vehicleModel)]
        ], [
            'brand.exists' => 'The vehicle :attribute does not exist'
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the vehicle model
        $vehicleModel->forceFill([
            'vehicle_brand_id' => $brand,
            'name' => $name
        ])->save();

        return $this->sendSuccess('Vehicle model updated successfully', $vehicleModel);
    }

    /**
     * Delete a vehicle model
     */
    public function destroy($vehicleModelId)
    {
        $vehicleModel = VehicleModel::find($vehicleModelId);

        if (is_null($vehicleModel)) {
            return $this->sendErrorMessage('Vehicle model not found', 404);
        }

        $vehicleModel->delete();

        return $this->sendSuccess('Vehicle model deleted successfully');
    }

}
