<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VehiclesBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            BikeBrandSeeder::class,
            CarBrandSeeder::class,
            // TruckBrandSeeder::class
        ]);
    }
}
