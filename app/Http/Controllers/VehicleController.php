<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\{Vehicle, Role};
use App\Services\Files\Upload;

class VehicleController extends Controller
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
     * Get all the vehicles
     */
    public function index()
    {
        // If the user is an administrator, make the amount per km, average speed visible and currency visible
        if (auth()->user()?->role_id === Role::ADMINISTRATOR) {
            $vehicles = Vehicle::withTrashed()->get();

            $vehicles->each->makeVisible([
                'average_speed_km_per_hour',
                'amount_per_km',
                'currency'
            ]);
        } else {
            $vehicles = Vehicle::all();
        }

        return $this->sendSuccess('Request successful', $vehicles);
    }

    /**
     * Create a vehicle
     */
    public function store()
    {
        $validator = validator()->make(request()->all(), [
            'name' => ['required', 'unique:vehicles'],
            'average_speed_km_per_hour' => ['required', 'integer'],
            'amount_per_km' => ['required', 'integer'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Check if an image was uploaded
        if (isset($image)) {
            // Upload the image
            $imagePath = $image->storePublicly('photos/vehicles');
        }

        $vehicle = Vehicle::create([
            'name' => $name,
            'average_speed_km_per_hour' => $average_speed_km_per_hour,
            'amount_per_km' => $amount_per_km,
            'currency' => config('handova.currency'),
            'image' => $imagePath ?? null
        ]);

        return $this->sendSuccess('Vehicle created successfully', $vehicle->makeVisible([
            'average_speed_km_per_hour',
            'amount_per_km',
            'currency'
        ]), 201);
    }

    /**
     * Get a vehicle
     */
    public function show($vehicleId)
    {
        if (auth()->user()?->role_id === Role::ADMINISTRATOR) {
            $vehicle = Vehicle::withTrashed()->find($vehicleId);
        } else {
            $vehicle = Vehicle::find($vehicleId);
        }

        if (is_null($vehicle)) {
            return $this->sendErrorMessage('Vehicle not found', 404);
        }

        // If the user is an administrator, make the amount per km, average speed visible and currency visible
        if (auth()->user()?->role_id === Role::ADMINISTRATOR) {
            $vehicle->makeVisible([
                'average_speed_km_per_hour',
                'amount_per_km',
                'currency'
            ]);
        }

        return $this->sendSuccess('Request successful', $vehicle);
    }

    /**
     * Get the brands of a vehicle
     */
    public function showBrands($vehicleId)
    {
        if (auth()->user()?->role_id === Role::ADMINISTRATOR) {
            $vehicle = Vehicle::withTrashed()->with(['vehicleBrands'])->find($vehicleId);
        } else {
            $vehicle = Vehicle::with(['vehicleBrands'])->find($vehicleId);
        }

        if (is_null($vehicle)) {
            return $this->sendErrorMessage('Vehicle not found', 404);
        }

        return $this->sendSuccess('Request successful', $vehicle->vehicleBrands);
    }

    /**
     * Update a vehicle
     */
    public function update($vehicleId)
    {
        $vehicle = Vehicle::withTrashed()->find($vehicleId);

        if (is_null($vehicle)) {
            return $this->sendErrorMessage('Vehicle not found', 404);
        }

        $validator = validator()->make(request()->all(), [
            'name' => ['required', 'unique:vehicles,name,'.$vehicle->id],
            'average_speed_km_per_hour' => ['required', 'integer'],
            'amount_per_km' => ['required', 'integer'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Check if an image was uploaded
        if (isset($image)) {
            // Upload the new profile image
            $imagePath = $image->storePublicly('photos/vehicle');

            // Check if there was a previous image
            if (!is_null($vehicle->image)) {
                // Get the old image path
                $oldImagePath = app()->make(Upload::class)->pathFromUrl($vehicle->image);

                // Delete the old image
                Storage::delete($oldImagePath);
            }
        }

        $vehicle->update([
            'name' => $name,
            'average_speed_km_per_hour' => $average_speed_km_per_hour,
            'amount_per_km' => $amount_per_km,
            'currency' => config('handova.currency'),
            'image' => $imagePath ?? $this->resolveImage($vehicle->image)
        ]);

        return $this->sendSuccess('Vehicle updated successfully', $vehicle->makeVisible([
            'average_speed_km_per_hour',
            'amount_per_km',
            'currency'
        ]));
    }

    /**
     * Delete a vehicle
     */
    public function destroy($vehicleId)
    {
        $vehicle = Vehicle::withTrashed()->find($vehicleId);

        if (is_null($vehicle)) {
            return $this->sendErrorMessage('Vehicle not found', 404);
        }

        if (!is_null($vehicle->image)) {
            $imagePath = app()->make(Upload::class)->pathFromUrl($vehicle->image);

            // Delete the associated image
            Storage::delete($imagePath);
        }

        $vehicle->forceDelete();

        return $this->sendSuccess('Vehicle deleted successfully');
    }

    /**
     * Disable the service of a vehicle
     */
    public function disable($vehicleId)
    {
        $vehicle = Vehicle::withTrashed()->find($vehicleId);

        if (is_null($vehicle)) {
            return $this->sendErrorMessage('Vehicle not found', 404);
        }

        // Check if the vehicle is already disabled
        if ($vehicle->trashed()) {
            return $this->sendSuccess('Vehicle has already been disabled');
        }

        // Make the vehicle service unavailable
        $vehicle->delete();

        return $this->sendSuccess('"'.ucwords($vehicle->name).'" services is now unavailable');
    }

    /**
     * Enable the service of a vehicle
     */
    public function enable($vehicleId)
    {
        $vehicle = Vehicle::withTrashed()->find($vehicleId);

        if (is_null($vehicle)) {
            return $this->sendErrorMessage('Vehicle not found', 404);
        }

        // Check if the vehicle is already enabled
        if (!$vehicle->trashed()) {
            return $this->sendSuccess('Vehicle is already enabled');
        }

        // Make the vehicle service available
        $vehicle->restore();

        return $this->sendSuccess('"'.ucwords($vehicle->name).'" services is now available');
    }

    /**
     * Resolve an updated image
     */
    private function resolveImage($image)
    {
        return app()->make(Upload::class)->pathFromUrl($image) ?? null;
    }

}
