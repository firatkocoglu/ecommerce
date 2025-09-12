<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //        $categories = \App\Models\Category::factory()->count(5)->create();

        \App\Models\Product::factory()
            ->count(50)
            ->create();
    }
}
