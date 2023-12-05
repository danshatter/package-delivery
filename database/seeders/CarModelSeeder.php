<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\{Vehicle, VehicleBrand};

class CarModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the data for the car models
        $json = Storage::disk('base')->get('car-models.json');

        // Decode the JSON data of the car models
        $data = json_decode($json, true);

        // Get the car brand
        $carBrands = VehicleBrand::where('vehicle_id', Vehicle::CARS)->get();

        /**
         * For each car brand, we get the models belonging to those cars
         * then create the models
         */
        $carBrands->each(function($carBrand) use ($data) {
            // Filter the data to return the car models belonging to the brand
            $models = collect($data['data'])->where('brand_id', $carBrand->old_brand_id)->map(fn($model) => [
                'name' => $model['name']
            ])->toArray();

            // Create the model of the car
            $carBrand->vehicleModels()->createMany($models);
        });
    }
}
