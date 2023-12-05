<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\{Vehicle, VehicleBrand};

class BikeModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the data for the bike models
        $json = Storage::disk('base')->get('bike-models.json');

        // Decode the JSON data of the bike models
        $data = json_decode($json, true);

        // Get the bike brand
        $bikeBrands = VehicleBrand::where('vehicle_id', Vehicle::BIKES)->get();

        /**
         * For each bike brand, we get the models belonging to those bikes
         * then create the models
         */
        $bikeBrands->each(function($bikeBrand) use ($data) {
            // Filter the data to return the bike models belonging to the brand
            $models = collect($data['data'])->where('brand_id', $bikeBrand->old_brand_id)->map(fn($model) => [
                'name' => $model['name']
            ])->toArray();

            // Create the model of the bike
            $bikeBrand->vehicleModels()->createMany($models);
        });
    }
}
