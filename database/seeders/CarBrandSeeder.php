<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Vehicle;

class CarBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the data for the car brands
        $json = Storage::disk('base')->get('car-brands.json');

        // Decode the JSON data of the cars
        $decoded = json_decode($json, true);

        // The data to store in the database
        $data = collect($decoded['data'])->map(fn($brand) => [
            'old_brand_id' => $brand['id'],
            'name' => $brand['name']
        ])->toArray();

        // Get the car instance
        $car = Vehicle::find(Vehicle::CARS);

        // Store the data in the database
        $car->vehicleBrands()->createMany($data);
    }
}
