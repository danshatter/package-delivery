<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VehiclesModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            BikeModelSeeder::class,
            CarModelSeeder::class,
            // TruckModelSeeder::class
        ]);
    }
}
