<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // The seeds of the roles of the application
        Role::create([
            'id' => Role::CUSTOMER,
            'name' => 'Customer'
        ]);
        Role::create([
            'id' => Role::DRIVER,
            'name' => 'Driver'
        ]);
        Role::create([
            'id' => Role::ADMINISTRATOR,
            'name' => 'Administrator'
        ]);
    }
}
