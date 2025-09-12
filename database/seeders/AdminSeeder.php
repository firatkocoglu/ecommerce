<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Admin::factory()->create([
            'name' => 'Fırat Koçoğlu',
            'email' => 'fratkocogl@gmail.com',
            'password' => env('ADMIN_PASSWORD'),
        ]);

        $admin->assignRole('admin');
    }
}
