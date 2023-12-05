<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Vehicle;

class BikeBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the data for the bike brands
        $json = Storage::disk('base')->get('bike-brands.json');

        // Decode the JSON data of the bikes
        $decoded = json_decode($json, true);

        // The data to store in the database
        $data = collect($decoded['data'])->map(fn($brand) => [
            'old_brand_id' => $brand['id'],
            'name' => $brand['name']
        ])->toArray();

        // Get the bike instance
        $bike = Vehicle::find(Vehicle::BIKES);

        // Store the data in the database
        $bike->vehicleBrands()->createMany($data);
    }
}
