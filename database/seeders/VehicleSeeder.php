<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seeds for test data for vehicles
        Vehicle::create([
            'id' => Vehicle::BIKES,
            'name' => 'Bikes',
            'amount_per_km' => 50000,
            'currency' => config('handova.currency'),
            'average_speed_km_per_hour' => 50
        ]);
        Vehicle::create([
            'id' => Vehicle::CARS,
            'name' => 'Cars',
            'amount_per_km' => 70000,
            'currency' => config('handova.currency'),
            'average_speed_km_per_hour' => 40
        ]);
        Vehicle::create([
            'id' => Vehicle::TRUCKS,
            'name' => 'Trucks',
            'amount_per_km' => 80000,
            'currency' => config('handova.currency'),
            'average_speed_km_per_hour' => 30
        ]);
    }
}
