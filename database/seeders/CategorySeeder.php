<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seeds for test data for categories
        Category::create(['name' => 'Furnitures']);
        Category::create(['name' => 'Clothings']);
        Category::create(['name' => 'Utensils']);
        Category::create(['name' => 'Electronics']);
    }
}
